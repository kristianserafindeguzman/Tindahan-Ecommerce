import pandas as pd
import numpy as np
import pymysql
import warnings

from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score

# Suppress pandas warning about not using sqlalchemy
warnings.filterwarnings('ignore', category=UserWarning, module='pandas')


# ============================================================
# 1. LOAD SEARCH LOGS
# ============================================================

print("================================")
print("LOADING SEARCH LOGS")
print("================================")

try:
    conn = pymysql.connect(
        host='127.0.0.1',
        user='root',
        password='',
        database='tindahan_db'
    )
    query = "SELECT * FROM search_logs"
    data = pd.read_sql(query, conn)
    conn.close()
    print("Successfully loaded search_logs from database.")
except Exception as e:
    print(f"ERROR connecting to database: {e}")
    exit()

print("\nRaw search logs:")
print(data.head())

print(f"\nTotal search logs: {len(data)}")


# ============================================================
# 2. CHECK REQUIRED COLUMNS
# ============================================================

required_columns = [
    "log_id",
    "consumer_id",
    "category_id",
    "search_query",
    "search_lat",
    "search_lng",
    "searched_at"
]

missing_columns = [
    column for column in required_columns
    if column not in data.columns
]

if missing_columns:
    print("\nERROR: Missing columns:")
    print(missing_columns)
    exit()


# ============================================================
# 3. CLEAN DATA
# ============================================================

data["searched_at"] = pd.to_datetime(
    data["searched_at"],
    errors="coerce"
)

data["category_id"] = pd.to_numeric(
    data["category_id"],
    errors="coerce"
)

data["search_lat"] = pd.to_numeric(
    data["search_lat"],
    errors="coerce"
)

data["search_lng"] = pd.to_numeric(
    data["search_lng"],
    errors="coerce"
)


# Remove invalid records

data = data.dropna(
    subset=[
        "category_id",
        "search_lat",
        "search_lng",
        "searched_at"
    ]
)


print(f"\nValid search logs: {len(data)}")


# ============================================================
# 4. CREATE TIME FEATURES
# ============================================================

data["hour"] = data["searched_at"].dt.hour

data["day_of_week"] = data["searched_at"].dt.dayofweek

data["date"] = data["searched_at"].dt.date


# ============================================================
# 5. DISPLAY CATEGORY DISTRIBUTION
# ============================================================

print("\n================================")
print("CATEGORY DISTRIBUTION")
print("================================")

category_counts = (
    data["category_id"]
    .value_counts()
    .sort_index()
)

print(category_counts)

print(
    f"\nNumber of categories: "
    f"{data['category_id'].nunique()}"
)


# ============================================================
# 6. CREATE SEARCH DEMAND DATA
# ============================================================
#
# Instead of grouping by the exact date, we group searches
# according to
#
# category
# location
# hour
# day of week
#
# This allows the Random Forest to learn recurring
# search-demand patterns
#

training_data = (
    data.groupby(
        [
            "category_id",
            "search_lat",
            "search_lng",
            "hour",
            "day_of_week"
        ]
    )
    .size()
    .reset_index(name="search_count")
)


print("\n================================")
print("TRAINING DATA")
print("================================")

print(training_data.head(10))

print(
    f"\nTraining records: "
    f"{len(training_data)}"
)


# ============================================================
# 7. DEMAND STATISTICS
# ============================================================

print("\n================================")
print("DEMAND STATISTICS")
print("================================")

print(
    training_data["search_count"].describe()
)


# ============================================================
# 8. FEATURES
# ============================================================

features = [
    "category_id",
    "search_lat",
    "search_lng",
    "hour",
    "day_of_week"
]

X = training_data[features]

# Target variable:
# Number of searches for the given
# category + location + hour + day

y = training_data["search_count"]


# ============================================================
# 9. TRAIN / TEST SPLIT
# ============================================================

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.20,
    random_state=42
)


print("\n================================")
print("DATASET SPLIT")
print("================================")

print(f"Training samples: {len(X_train)}")
print(f"Testing samples: {len(X_test)}")


# ============================================================
# 10. RANDOM FOREST REGRESSOR
# ============================================================

model = RandomForestRegressor(
    n_estimators=200,
    max_depth=12,
    min_samples_split=2,
    min_samples_leaf=1,
    random_state=42,
    n_jobs=-1
)


print("\nTraining Random Forest...")

model.fit(X_train, y_train)


print("Training complete.")


# ============================================================
# 11. MAK PREDICTIONS
# ============================================================

predictions = model.predict(X_test)


# ============================================================
# 12. EVALUATE MODEL
# ============================================================

mae = mean_absolute_error(
    y_test,
    predictions
)

r2 = r2_score(
    y_test,
    predictions
)


print("\n================================")
print("RANDOM FOREST RESULTS")
print("================================")

print(
    f"Mean Absolute Error: {mae:.2f}"
)

print(
    f"R2 Score: {r2:.2f}"
)


# ============================================================
# 13. FEATURE IMPORTANCE
# ============================================================

print("\n================================")
print("FEATURE IMPORTANCE")
print("================================")

importance = pd.DataFrame({
    "feature": features,
    "importance": model.feature_importances_
})

importance = importance.sort_values(
    by="importance",
    ascending=False
)

print(importance)


# ============================================================
# 14. EXAMPLE DEMAND PREDICTIONS
# ============================================================
#
# Category 1 = Cooking Essentials
#
# Location:
# 14.64067907, 121.06565020
#
# Time:
# Saturday, 19:00
#

example = pd.DataFrame({
    "category_id": [1],
    "search_lat": [14.64067907],
    "search_lng": [121.06565020],
    "hour": [19],
    "day_of_week": [5]
})


predicted_demand = model.predict(
    example
)[0]


# Demand should not be negative

predicted_demand = max(
    0,
    predicted_demand
)


print("\n================================")
print("EXAMPLE DEMAND PREDICTION")
print("================================")

print("Category ID: 1")

print(
    "Location: "
    "14.64067907, 121.06565020"
)

print("Hour: 19:00")

print("Day of week: Saturday")

print(
    f"Predicted search demand: "
    f"{predicted_demand:.2f}"
)


# ============================================================
# 15. DEMAND INTERPRETATION
# ============================================================

print("\n================================")
print("DEMAND INTERPRETATION")
print("================================")


if predicted_demand < 2:

    demand_level = "LOW"

elif predicted_demand < 5:

    demand_level = "MODERATE"

elif predicted_demand < 10:

    demand_level = "HIGH"

else:

    demand_level = "VERY HIGH"


print(
    f"Demand level: {demand_level}"
)


# ============================================================
# 16. SAVE MODEL RESULT
# ============================================================

results = X_test.copy()

results["actual_demand"] = y_test.values

results["predicted_demand"] = predictions

results["prediction_error"] = (
    results["actual_demand"]
    - results["predicted_demand"]
)

results.to_csv(
    "random_forest_personalization_results.csv",
    index=False
)


print("\nResults saved to:")
print("random_forest_personalization_results.csv")

print("\n================================")
print("RANDOM FOREST COMPLETE")
print("================================")