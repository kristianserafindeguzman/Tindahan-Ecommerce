import pandas as pd
import numpy as np
import pymysql
import warnings
import argparse
import json
import pickle
import sys
import os
from datetime import timedelta
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error

warnings.filterwarnings('ignore', category=UserWarning, module='pandas')

def get_db_connection():
    return pymysql.connect(
        host='127.0.0.1',
        user='root',
        password='',
        database='tindahan_db'
    )

def fetch_search_logs():
    try:
        conn = get_db_connection()
        query = "SELECT log_id, consumer_id, category_id, search_query, search_lat, search_lng, searched_at FROM search_logs"
        df = pd.read_sql(query, conn)
        conn.close()
        
        df["searched_at"] = pd.to_datetime(df["searched_at"], errors="coerce")
        df["search_lat"] = pd.to_numeric(df["search_lat"], errors="coerce")
        df["search_lng"] = pd.to_numeric(df["search_lng"], errors="coerce")
        # Keep category_id as float (can be NaN), drop rows missing critical fields
        df = df.dropna(subset=["consumer_id", "search_lat", "search_lng", "searched_at"])
        return df
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Database error: {str(e)}"}))
        sys.exit(1)

def build_features(history_df, T):
    """
    Builds features from history_df relative to time T.
    Only considers rows with a valid category_id for Path A.
    """
    valid_history = history_df.dropna(subset=["category_id"]).copy()
    if valid_history.empty:
        return pd.DataFrame()
        
    valid_history["category_id"] = valid_history["category_id"].astype(int)
    
    # Feature 1: frequency
    freq = valid_history.groupby(["consumer_id", "category_id"]).size().reset_index(name="frequency")
    
    # Feature 2: recency (days since last search relative to T)
    last_search = valid_history.groupby(["consumer_id", "category_id"])["searched_at"].max().reset_index()
    last_search["days_since_last"] = (T - last_search["searched_at"]).dt.days
    
    # Feature 3 & 4: dominant hour and day
    valid_history["hour"] = valid_history["searched_at"].dt.hour
    valid_history["day_of_week"] = valid_history["searched_at"].dt.dayofweek
    
    # Mode can return multiple, so take the first
    dom_hour = valid_history.groupby(["consumer_id", "category_id"])["hour"].apply(lambda x: x.mode().iloc[0]).reset_index(name="dominant_hour")
    dom_day = valid_history.groupby(["consumer_id", "category_id"])["day_of_week"].apply(lambda x: x.mode().iloc[0]).reset_index(name="dominant_day")
    
    # Merge all
    features = freq.merge(last_search[["consumer_id", "category_id", "days_since_last"]], on=["consumer_id", "category_id"])
    features = features.merge(dom_hour, on=["consumer_id", "category_id"])
    features = features.merge(dom_day, on=["consumer_id", "category_id"])
    
    return features

def build_target(future_df):
    """
    Builds target from future_df.
    """
    valid_future = future_df.dropna(subset=["category_id"]).copy()
    if valid_future.empty:
        return pd.DataFrame(columns=["consumer_id", "category_id", "future_search_count"])
        
    valid_future["category_id"] = valid_future["category_id"].astype(int)
    target = valid_future.groupby(["consumer_id", "category_id"]).size().reset_index(name="future_search_count")
    return target

def train_model(df):
    model_path = os.path.join(os.path.dirname(__file__), 'models', 'personalization_model.pkl')
    os.makedirs(os.path.dirname(model_path), exist_ok=True)
    
    if df.empty:
        print(json.dumps({"status": "error", "message": "No search logs available."}))
        sys.exit(1)
        
    max_date = df["searched_at"].max()
    min_date = df["searched_at"].min()
    
    # If we have less than 2 days of data, temporal split is impossible.
    if (max_date - min_date).days < 2:
        print(json.dumps({"status": "error", "message": "Insufficient temporal data for training split. Need at least 2 days of search history."}))
        sys.exit(1)
        
    # Split T: 7 days before max_date, or mid-point if less than 14 days
    split_days = min(7, max(1, (max_date - min_date).days // 2))
    T = max_date - timedelta(days=split_days)
    
    history_df = df[df["searched_at"] <= T]
    future_df = df[df["searched_at"] > T]
    
    features = build_features(history_df, T)
    target = build_target(future_df)
    
    if features.empty:
        print(json.dumps({"status": "error", "message": "No valid categorical searches in history for training."}))
        sys.exit(1)
        
    # Left join to ensure consumers who didn't search in future get a 0 target
    dataset = features.merge(target, on=["consumer_id", "category_id"], how="left")
    dataset["future_search_count"] = dataset["future_search_count"].fillna(0)
    
    X = dataset[["category_id", "frequency", "days_since_last", "dominant_hour", "dominant_day"]]
    y = dataset["future_search_count"]
    
    model = RandomForestRegressor(n_estimators=100, max_depth=10, random_state=42)
    model.fit(X, y)
    
    with open(model_path, 'wb') as f:
        pickle.dump(model, f)
        
    # Evaluate on training validation
    predictions = model.predict(X)
    mae = mean_absolute_error(y, predictions)
    rmse = np.sqrt(mean_squared_error(y, predictions))
    
    # We do NOT save a CSV here. Training operates silently when successful.
    print(json.dumps({
        "status": "success", 
        "message": "Model trained successfully.",
        "metrics": {"mae": round(mae, 4), "rmse": round(rmse, 4)},
        "split_T": str(T),
        "history_rows": len(history_df),
        "future_rows": len(future_df)
    }))

def predict(df):
    model_path = os.path.join(os.path.dirname(__file__), 'models', 'personalization_model.pkl')
    
    output = {"status": "success", "path_a": [], "path_b": []}
    
    # ---------------------------------------------------------
    # PATH A: Individual Personalization
    # ---------------------------------------------------------
    try:
        if os.path.exists(model_path):
            with open(model_path, 'rb') as f:
                model = pickle.load(f)
                
            T = df["searched_at"].max()
            if pd.isna(T):
                T = pd.Timestamp.now()
                
            features = build_features(df, T)
            if not features.empty:
                X = features[["category_id", "frequency", "days_since_last", "dominant_hour", "dominant_day"]]
                predictions = model.predict(X)
                features["predicted_score"] = predictions
                
                # Format output
                for _, row in features.iterrows():
                    output["path_a"].append({
                        "consumer_id": int(row["consumer_id"]),
                        "category_id": int(row["category_id"]),
                        "predicted_score": float(row["predicted_score"])
                    })
    except Exception as e:
        # If Path A fails (e.g. no model), we just return empty Path A. Path B still runs.
        pass

    # ---------------------------------------------------------
    # PATH B: Localized Popular Searches
    # ---------------------------------------------------------
    try:
        if not df.empty:
            # Use last 30 days for localized popularity
            T_max = df["searched_at"].max()
            T_30 = T_max - timedelta(days=30)
            recent_logs = df[df["searched_at"] >= T_30].copy()
            
            if not recent_logs.empty:
                # Round to 2 decimal places (~1.1km grid)
                recent_logs["lat_grid"] = recent_logs["search_lat"].round(2)
                recent_logs["lng_grid"] = recent_logs["search_lng"].round(2)
                
                # Group by grid, query, and category (category can be null)
                # Fill NaN category with -1 temporarily for grouping
                recent_logs["category_id"] = recent_logs["category_id"].fillna(-1)
                
                agg = recent_logs.groupby(["lat_grid", "lng_grid", "search_query", "category_id"]).size().reset_index(name="search_count")
                
                for _, row in agg.iterrows():
                    cat_id = None if row["category_id"] == -1 else int(row["category_id"])
                    output["path_b"].append({
                        "lat_grid": float(row["lat_grid"]),
                        "lng_grid": float(row["lng_grid"]),
                        "search_query": str(row["search_query"]),
                        "category_id": cat_id,
                        "search_count": int(row["search_count"])
                    })
    except Exception as e:
        pass

    print(json.dumps(output))

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--mode", choices=["train", "predict"], required=True)
    args = parser.parse_args()
    
    df = fetch_search_logs()
    
    if args.mode == "train":
        train_model(df)
    elif args.mode == "predict":
        predict(df)