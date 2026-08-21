import argparse
import json
import pandas as pd
import numpy as np
import pymysql
import warnings
import joblib
import os
import sys
from datetime import timedelta

from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error, r2_score

# Suppress pandas warning about not using sqlalchemy
warnings.filterwarnings('ignore', category=UserWarning, module='pandas')

def load_data():
    try:
        conn = pymysql.connect(
            host='127.0.0.1', user='root', password='', database='tindahan_db'
        )
        query = "SELECT * FROM ml_historical_sales_view"
        data = pd.read_sql(query, conn)
        conn.close()
        return data
    except Exception as e:
        return None

def engineer_features(data, is_predicting=False):
    # Ensure numeric types
    data["store_id"] = pd.to_numeric(data["store_id"], errors="coerce")
    data["inventory_id"] = pd.to_numeric(data["inventory_id"], errors="coerce")
    data["total_daily_quantity"] = pd.to_numeric(data["total_daily_quantity"], errors="coerce")
    data = data.dropna(subset=["store_id", "inventory_id", "total_daily_quantity"])
    data["transaction_date"] = pd.to_datetime(data["transaction_date"], errors="coerce")
    data = data.dropna(subset=["transaction_date"])

    # 4.1 Handle Missing Dates & Zero-fill Strategy
    group_dfs = []
    grouped = data.groupby(['store_id', 'inventory_id'])

    for (store_id, inv_id), group in grouped:
        min_date = group['transaction_date'].min()
        max_date = group['transaction_date'].max()
        
        if pd.isna(min_date) or pd.isna(max_date):
            continue
        
        # If predicting, we extend the range by 1 day to forecast for tomorrow
        if is_predicting:
            max_date = max_date + timedelta(days=1)
            
        full_date_range = pd.date_range(start=min_date, end=max_date, freq='D')
        
        group = group.set_index('transaction_date')
        group = group[~group.index.duplicated(keep='first')]
        group = group.reindex(full_date_range)
        
        group['store_id'] = store_id
        group['inventory_id'] = inv_id
        group['total_daily_quantity'] = group['total_daily_quantity'].fillna(0)
        group['season_category'] = group['season_category'].ffill().fillna('None')
        group['holiday_event'] = group['holiday_event'].ffill().fillna('None')
        
        group_dfs.append(group)

    if not group_dfs:
        return None, None
        
    df = pd.concat(group_dfs).rename_axis('transaction_date').reset_index()

    # 4.2 Generate Lag and Rolling Features
    df = df.sort_values(['store_id', 'inventory_id', 'transaction_date'])
    grouped_df = df.groupby(['store_id', 'inventory_id'])

    df['prev_day_sales'] = grouped_df['total_daily_quantity'].shift(1)
    df['prev_week_sales'] = grouped_df['total_daily_quantity'].shift(7)
    df['rolling_7d'] = grouped_df['total_daily_quantity'].shift(1).rolling(window=7, min_periods=1).sum()
    df['rolling_30d'] = grouped_df['total_daily_quantity'].shift(1).rolling(window=30, min_periods=1).sum()
    prev_week_rolling_7d = grouped_df['total_daily_quantity'].shift(8).rolling(window=7, min_periods=1).sum()
    df['trend'] = (df['rolling_7d'] - prev_week_rolling_7d) / np.maximum(prev_week_rolling_7d, 1)

    lag_features = ['prev_day_sales', 'prev_week_sales', 'rolling_7d', 'rolling_30d', 'trend']
    df[lag_features] = df[lag_features].fillna(0)

    # Extract temporal features
    df["day_of_week"] = df["transaction_date"].dt.dayofweek
    df["month"] = df["transaction_date"].dt.month

    # One-Hot Encoding for season and holiday
    categorical_cols = ["season_category", "holiday_event"]
    df_encoded = pd.get_dummies(df, columns=categorical_cols, drop_first=False)
    
    return df_encoded, df

def train_model(output_mode):
    data = load_data()
    if data is None or len(data) == 0:
        if output_mode == 'json':
            print(json.dumps({"status": "error", "message": "No historical data available"}))
        else:
            print("No data available to train.")
        return
        
    df_encoded, df = engineer_features(data, is_predicting=False)
    if df_encoded is None:
        if output_mode == 'json':
            print(json.dumps({"status": "error", "message": "Feature engineering failed"}))
        return
        
    target_col = "total_daily_quantity"
    cols_to_drop = ["transaction_date", target_col]
    
    df_encoded = df_encoded.sort_values('transaction_date')
    split_idx = int(len(df_encoded) * 0.8)
    
    train_data = df_encoded.iloc[:split_idx]
    test_data = df_encoded.iloc[split_idx:]
    
    X_train = train_data.drop(columns=cols_to_drop)
    y_train = train_data[target_col]
    X_test = test_data.drop(columns=cols_to_drop)
    y_test = test_data[target_col]
    
    model = RandomForestRegressor(n_estimators=200, max_depth=12, random_state=42, n_jobs=-1)
    
    model.fit(X_train, y_train)
    predictions = model.predict(X_test)
    y_test_array = y_test.values
    rmse = np.sqrt(mean_squared_error(y_test_array, predictions))
    r2 = r2_score(y_test_array, predictions)
    
    non_zero_mask = y_test_array != 0
    mape = np.nan
    if non_zero_mask.any():
        mape = np.mean(np.abs((y_test_array[non_zero_mask] - predictions[non_zero_mask]) / y_test_array[non_zero_mask])) * 100
        
    # Refit on all data to capture the most recent patterns before saving
    X_all = df_encoded.drop(columns=cols_to_drop)
    y_all = df_encoded[target_col]
    model.fit(X_all, y_all)
    
    # Save model and columns
    os.makedirs('ml/models', exist_ok=True)
    joblib.dump(model, 'ml/models/demand_model.pkl')
    joblib.dump(list(X_all.columns), 'ml/models/demand_columns.pkl')
    
    if output_mode == 'json':
        res = {
            "status": "success",
            "model_metrics": {
                "rmse": float(rmse),
                "mape": float(mape) if not np.isnan(mape) else None,
                "r2": float(r2)
            },
            "message": "Training complete"
        }
        print(json.dumps(res))
    else:
        print("MODEL EVALUATION")
        print(f"RMSE: {rmse:.4f}")
        print(f"MAPE: {mape:.2f}%" if not np.isnan(mape) else "MAPE: N/A")
        print(f"R²: {r2:.4f}")
        print("Model saved to ml/models/demand_model.pkl")

def predict_model(output_mode):
    try:
        model = joblib.load('ml/models/demand_model.pkl')
        train_cols = joblib.load('ml/models/demand_columns.pkl')
    except Exception as e:
        if output_mode == 'json':
            print(json.dumps({"status": "error", "message": f"Failed to load model: {e}"}))
        else:
            print(f"Failed to load model: {e}")
        return

    data = load_data()
    if data is None or len(data) == 0:
        if output_mode == 'json':
            print(json.dumps({"status": "error", "message": "No historical data available"}))
        return

    df_encoded, df = engineer_features(data, is_predicting=True)
    if df_encoded is None:
        if output_mode == 'json':
            print(json.dumps({"status": "error", "message": "Feature engineering failed"}))
        return
        
    target_col = "total_daily_quantity"
    cols_to_drop = ["transaction_date", target_col]
    
    # Align columns with training data
    X = df_encoded.drop(columns=cols_to_drop)
    for col in train_cols:
        if col not in X.columns:
            X[col] = 0
    X = X[train_cols]
    
    df_encoded['predicted_quantity'] = model.predict(X)
    
    # Extract just the latest date for each store-inventory pair (tomorrow's date)
    latest_dates = df_encoded.groupby(['store_id', 'inventory_id'])['transaction_date'].transform('max')
    forecast_df = df_encoded[df_encoded['transaction_date'] == latest_dates]
    
    forecasts = []
    for _, row in forecast_df.iterrows():
        forecasts.append({
            "store_id": int(row['store_id']),
            "inventory_id": int(row['inventory_id']),
            "forecast_date": row['transaction_date'].strftime('%Y-%m-%d'),
            "predicted_quantity": max(0, float(np.round(row['predicted_quantity'])))
        })
        
    if output_mode == 'json':
        res = {
            "status": "success",
            "forecasts": forecasts
        }
        print(json.dumps(res))
    else:
        print(f"Generated {len(forecasts)} forecasts.")
        print(pd.DataFrame(forecasts).head())

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Tindahan Random Forest Demand Forecasting")
    parser.add_argument("--mode", type=str, default="train", choices=["train", "predict"])
    parser.add_argument("--output", type=str, default="console", choices=["console", "json"])
    args = parser.parse_args()
    
    if args.mode == "train":
        train_model(args.output)
    elif args.mode == "predict":
        predict_model(args.output)