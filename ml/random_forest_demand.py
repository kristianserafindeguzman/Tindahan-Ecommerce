import pandas as pd
import numpy as np
import pymysql
import warnings

from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error
from sklearn.preprocessing import OneHotEncoder

# Suppress pandas warning about not using sqlalchemy
warnings.filterwarnings('ignore', category=UserWarning, module='pandas')

# ============================================================
# 1. LOAD HISTORICAL SALES DATA
# ============================================================

print("================================")
print("LOADING HISTORICAL SALES DATA")
print("================================")

# Connect to database
try:
    conn = pymysql.connect(
        host='127.0.0.1',
        user='root',
        password='',
        database='tindahan_db'
    )
    # The view 'ml_historical_sales_view' already aggregates sales by:
    # transaction_date, store_id, inventory_id, season_category, holiday_event
    # Target variable is 'total_daily_quantity'
    query = "SELECT * FROM ml_historical_sales_view"
    data = pd.read_sql(query, conn)
    conn.close()
    print("Successfully loaded data from database view.")
except Exception as e:
    print(f"ERROR connecting to database: {e}")
    exit()

print(f"\nTotal historical sales records: {len(data)}")
if len(data) == 0:
    print("No data available to train the model. Exiting.")
    exit()

print("\nSample records:")
print(data.head())


# ============================================================
# 2. CHECK REQUIRED COLUMNS
# ============================================================

required_columns = [
    "transaction_date",
    "store_id",
    "inventory_id",
    "total_daily_quantity",
    "season_category",
    "holiday_event"
]

missing_columns = [col for col in required_columns if col not in data.columns]
if missing_columns:
    print(f"\nERROR: Missing columns from view: {missing_columns}")
    exit()


# ============================================================
# 3. CLEAN & PREPARE DATA
# ============================================================

# Ensure numeric data types
data["store_id"] = pd.to_numeric(data["store_id"], errors="coerce")
data["inventory_id"] = pd.to_numeric(data["inventory_id"], errors="coerce")
data["total_daily_quantity"] = pd.to_numeric(data["total_daily_quantity"], errors="coerce")

# Drop nulls in crucial fields
data = data.dropna(subset=["store_id", "inventory_id", "total_daily_quantity"])


# ============================================================
# 4. FEATURE ENGINEERING
# ============================================================

data["transaction_date"] = pd.to_datetime(data["transaction_date"], errors="coerce")
data = data.dropna(subset=["transaction_date"])

# Extract temporal features
data["day_of_week"] = data["transaction_date"].dt.dayofweek
data["month"] = data["transaction_date"].dt.month

# Handle categorical fields explicitly (season_category, holiday_event)
# We fill missing strings just in case
data["season_category"] = data["season_category"].fillna("None")
data["holiday_event"] = data["holiday_event"].fillna("None")

print("\n================================")
print("FEATURE ENGINEERING")
print("================================")

# One-Hot Encoding for season and holiday
categorical_cols = ["season_category", "holiday_event"]
data_encoded = pd.get_dummies(data, columns=categorical_cols, drop_first=False)

# Define X (features) and y (target)
target_col = "total_daily_quantity"

# Features to exclude from X (we keep store_id, inventory_id, day_of_week, month, and all encoded categorical columns)
cols_to_drop = ["transaction_date", target_col]
X = data_encoded.drop(columns=cols_to_drop)
y = data_encoded[target_col]

print(f"Features used ({len(X.columns)}): {list(X.columns)}")
print(f"Target variable: {target_col}")


# ============================================================
# 5. TRAIN / TEST SPLIT
# ============================================================

# 80/20 split as per manuscript requirements
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

print("\n================================")
print("MODEL TRAINING")
print("================================")
print(f"Training set: {len(X_train)} samples")
print(f"Testing set: {len(X_test)} samples")


# ============================================================
# 6. MODEL INITIALIZATION & TRAINING
# ============================================================

model = RandomForestRegressor(
    n_estimators=200,
    max_depth=12,
    random_state=42,
    n_jobs=-1
)

model.fit(X_train, y_train)


# ============================================================
# 7. EVALUATION
# ============================================================

predictions = model.predict(X_test)
y_test_array = y_test.values

print("\n================================")
print("MODEL EVALUATION")
print("================================")

# RMSE (Root Mean Squared Error)
rmse = np.sqrt(mean_squared_error(y_test_array, predictions))
print(f"RMSE (Root Mean Squared Error): {rmse:.4f}")

# MAPE (Mean Absolute Percentage Error)
# Must handle zero values in actual demand to avoid division by zero
non_zero_mask = y_test_array != 0
if non_zero_mask.any():
    mape = np.mean(np.abs(
        (y_test_array[non_zero_mask] - predictions[non_zero_mask]) / y_test_array[non_zero_mask]
    )) * 100
    print(f"MAPE (Mean Absolute Percentage Error): {mape:.2f}%")
    
    if mape < 10.0:
        print("[SUCCESS] MAPE is under 10% target per manuscript requirements.")
    else:
        print("[WARNING] MAPE is above 10% target.")
else:
    print("MAPE cannot be calculated (all target values in test set are 0).")

# R² Score (Supplementary)
from sklearn.metrics import r2_score
r2 = r2_score(y_test_array, predictions)
print(f"R² Score: {r2:.4f}")


# ============================================================
# 8. FEATURE IMPORTANCE
# ============================================================

print("\n================================")
print("FEATURE IMPORTANCE")
print("================================")

importances = model.feature_importances_
feature_names = X.columns
importance_df = pd.DataFrame({
    'Feature': feature_names,
    'Importance': importances
}).sort_values(by='Importance', ascending=False)

print(importance_df.head(10).to_string(index=False))


# ============================================================
# 9. SAVE RESULTS
# ============================================================

results_df = X_test.copy()
results_df['actual_demand'] = y_test_array
results_df['predicted_demand'] = np.round(predictions).astype(int)
results_df['prediction_error'] = np.abs(results_df['actual_demand'] - results_df['predicted_demand'])

output_file = "random_forest_results.csv"
results_df.to_csv(output_file, index=False)
print(f"\nResults saved to {output_file}")
print("Random Forest forecasting complete.")