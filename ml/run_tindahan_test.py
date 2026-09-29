import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error, r2_score
import json
import os

print("================================")
print("RUNNING TINDAHAN TEST DATA")
print("================================")

# 1. Load Processed Data
try:
    data = pd.read_csv("ml/data/processed/tindahan_sales_daily_demand.csv")
    data["transaction_date"] = pd.to_datetime(data["transaction_date"], errors="coerce")
    data = data.dropna(subset=["transaction_date"])
except Exception as e:
    print(f"Failed to load processed data: {e}")
    exit(1)

# Ensure numeric data types
data["external_store_id"] = pd.to_numeric(data["external_store_id"], errors="coerce")
data["external_product_id"] = pd.to_numeric(data["external_product_id"], errors="coerce")
data["total_daily_quantity"] = pd.to_numeric(data["total_daily_quantity"], errors="coerce")

# Rename columns to match existing Random Forest script for easier processing
data.rename(columns={'external_store_id': 'store_id', 'external_product_id': 'inventory_id'}, inplace=True)

# 2. Enhanced Feature Generation
print("Generating lag and rolling features...")
data = data.sort_values(['store_id', 'inventory_id', 'transaction_date'])
grouped = data.groupby(['store_id', 'inventory_id'])

data['prev_day_sales'] = grouped['total_daily_quantity'].shift(1)
data['prev_week_sales'] = grouped['total_daily_quantity'].shift(7)
data['rolling_7d'] = grouped['total_daily_quantity'].shift(1).rolling(window=7, min_periods=1).sum()
data['rolling_30d'] = grouped['total_daily_quantity'].shift(1).rolling(window=30, min_periods=1).sum()
prev_week_rolling_7d = grouped['total_daily_quantity'].shift(8).rolling(window=7, min_periods=1).sum()
data['trend'] = (data['rolling_7d'] - prev_week_rolling_7d) / np.maximum(prev_week_rolling_7d, 1)

lag_features = ['prev_day_sales', 'prev_week_sales', 'rolling_7d', 'rolling_30d', 'trend']
data[lag_features] = data[lag_features].fillna(0)

# Temporal & Categorical
data["day_of_week"] = data["transaction_date"].dt.dayofweek
data["month"] = data["transaction_date"].dt.month
categorical_cols = ["season_category", "holiday_event", "aisle"]
data_encoded = pd.get_dummies(data, columns=categorical_cols, drop_first=False)

data_encoded = data_encoded.sort_values('transaction_date')

target_col = "total_daily_quantity"
cols_to_drop = ["transaction_date", target_col]

# Define Feature Sets
# Baseline: Just basic features
baseline_features = [c for c in data_encoded.columns if c not in cols_to_drop and c not in lag_features]
# Enhanced: Includes lag_features
enhanced_features = [c for c in data_encoded.columns if c not in cols_to_drop]

split_idx = int(len(data_encoded) * 0.8)
train_data = data_encoded.iloc[:split_idx]
test_data = data_encoded.iloc[split_idx:]

y_train = train_data[target_col].values
y_test = test_data[target_col].values

print(f"Total records (after preprocessing zero-fill): {len(data_encoded)}")
print(f"Training set: {len(y_train)} samples")
print(f"Testing set: {len(y_test)} samples")

results = []

def evaluate_model(features, name):
    print(f"\n================================")
    print(f"MODEL: {name}")
    print(f"================================")
    X_train = train_data[features]
    X_test = test_data[features]
    
    model = RandomForestRegressor(n_estimators=200, max_depth=12, random_state=42, n_jobs=-1)
    model.fit(X_train, y_train)
    
    predictions = model.predict(X_test)
    
    rmse = np.sqrt(mean_squared_error(y_test, predictions))
    r2 = r2_score(y_test, predictions)
    
    non_zero_mask = y_test != 0
    if non_zero_mask.any():
        mape = np.mean(np.abs((y_test[non_zero_mask] - predictions[non_zero_mask]) / y_test[non_zero_mask])) * 100
    else:
        mape = np.nan
        
    print(f"RMSE: {rmse:.4f}")
    print(f"MAPE: {mape:.2f}%" if not np.isnan(mape) else "MAPE: N/A")
    print(f"R²: {r2:.4f}")
    
    if name == 'ENHANCED':
        importances = model.feature_importances_
        importance_df = pd.DataFrame({'Feature': features, 'Importance': importances}).sort_values(by='Importance', ascending=False)
        print("\nFeature Importance:")
        print(importance_df.head(10).to_string(index=False))
        
    results.append({
        'Model': name,
        'RMSE': float(rmse),
        'MAPE': float(mape) if not np.isnan(mape) else None,
        'R2': float(r2)
    })

evaluate_model(baseline_features, 'BASELINE')
evaluate_model(enhanced_features, 'ENHANCED')

results_df = pd.DataFrame(results)
os.makedirs('ml/results', exist_ok=True)
results_df.to_csv('ml/results/tindahan_test_results.csv', index=False)
print("\nResults saved to ml/results/tindahan_test_results.csv")
