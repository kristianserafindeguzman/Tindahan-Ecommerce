import argparse
import os
import sys
import pandas as pd
import numpy as np
import pymysql
import warnings
from dotenv import load_dotenv
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error

warnings.filterwarnings('ignore', category=UserWarning, module='pandas')

# Import feature engineering from the production model
try:
    from random_forest_demand import engineer_features
except ImportError:
    print("Error: Could not import engineer_features from random_forest_demand.py.")
    print("Ensure this script is run from the ml/ directory or the repository root with PYTHONPATH set.")
    sys.exit(1)

def load_validation_data():
    """Loads historical sales data from the local validation database."""
    env_path = os.path.join(os.path.dirname(__file__), '..', '.env.validation')
    if not os.path.exists(env_path):
        # Fallback to current directory if not in ml/
        env_path = os.path.join(os.getcwd(), '.env.validation')
        
    if not os.path.exists(env_path):
        print(f"Error: {env_path} not found.")
        print("Please create .env.validation with DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD")
        sys.exit(1)

    load_dotenv(env_path)

    try:
        conn = pymysql.connect(
            host=os.getenv('DB_HOST', '127.0.0.1'),
            port=int(os.getenv('DB_PORT', 3306)),
            user=os.getenv('DB_USERNAME', 'root'),
            password=os.getenv('DB_PASSWORD', ''),
            database=os.getenv('DB_DATABASE', 'tindahan_ecommerce_validation')
        )
        query = "SELECT * FROM ml_historical_sales_view"
        data = pd.read_sql(query, conn)
        conn.close()
        return data
    except Exception as e:
        print(f"Database connection error: {e}")
        print("Make sure your local MySQL/MariaDB is running and the validation database is set up.")
        sys.exit(1)

def calculate_mape(actual, predicted):
    """Calculates Mean Absolute Percentage Error (MAPE)."""
    actual = np.array(actual)
    predicted = np.array(predicted)
    
    # Filter out zero actuals to prevent division by zero
    non_zero_mask = actual != 0
    if not non_zero_mask.any():
        return None, 0, len(actual)
        
    actual_nz = actual[non_zero_mask]
    predicted_nz = predicted[non_zero_mask]
    
    mape = np.mean(np.abs((actual_nz - predicted_nz) / actual_nz)) * 100
    zero_count = len(actual) - non_zero_mask.sum()
    valid_count = non_zero_mask.sum()
    
    return float(mape), int(zero_count), int(valid_count)

def calculate_rmse(actual, predicted):
    """Calculates Root Mean Squared Error (RMSE)."""
    return float(np.sqrt(mean_squared_error(actual, predicted)))

def validate_model(args):
    print("=" * 50)
    print("RANDOM FOREST MODEL VALIDATION")
    print("=" * 50)
    print("Data Source:\nLocal MySQL/MariaDB Database\n")
    print(f"Database:\n{os.getenv('DB_DATABASE', 'tindahan_ecommerce_validation')}\n")
    print("Model:\nRandom Forest Demand Forecasting\n")
    print("Validation Method:\nChronological Holdout\n")
    
    data = load_validation_data()
    if data is None or len(data) == 0:
        print("Error: No historical data found in ml_historical_sales_view.")
        return

    # Process features using the actual production function
    df_encoded, df = engineer_features(data, is_predicting=False)
    
    if df_encoded is None or len(df_encoded) == 0:
        print("Error: Feature engineering resulted in an empty dataset.")
        return

    # Chronological sort for holdout validation
    df_encoded = df_encoded.sort_values('transaction_date')
    
    target_col = "total_daily_quantity"
    cols_to_drop = ["transaction_date", target_col]
    
    # 80/20 chronological split (same as production)
    split_idx = int(len(df_encoded) * 0.8)
    
    train_data = df_encoded.iloc[:split_idx]
    test_data = df_encoded.iloc[split_idx:]
    
    if len(train_data) == 0 or len(test_data) == 0:
        print("Error: Insufficient data for chronological split.")
        return
        
    train_start = train_data['transaction_date'].min().strftime('%Y-%m-%d')
    train_end = train_data['transaction_date'].max().strftime('%Y-%m-%d')
    test_start = test_data['transaction_date'].min().strftime('%Y-%m-%d')
    test_end = test_data['transaction_date'].max().strftime('%Y-%m-%d')
    
    print(f"Training Period:\n{train_start} to {train_end}\n")
    print(f"Validation Period:\n{test_start} to {test_end}\n")
    
    print(f"Training Observations:\n{len(train_data)}\n")
    print(f"Validation Observations:\n{len(test_data)}\n")
    
    X_train = train_data.drop(columns=cols_to_drop)
    y_train = train_data[target_col]
    
    X_test = test_data.drop(columns=cols_to_drop)
    y_test = test_data[target_col]
    
    # Initialize same Random Forest as production
    model = RandomForestRegressor(n_estimators=200, max_depth=12, random_state=42, n_jobs=-1)
    
    print("Training model...")
    model.fit(X_train, y_train)
    
    print("Generating validation predictions...\n")
    predictions = model.predict(X_test)
    
    # Calculate overall metrics
    mape, zero_count, valid_count = calculate_mape(y_test, predictions)
    rmse = calculate_rmse(y_test, predictions)
    
    print(f"Zero-Demand Observations:\n{zero_count}\n")
    print(f"Valid MAPE Observations:\n{valid_count}\n")
    
    if mape is not None:
        print(f"MAPE:\n{mape:.2f} %\n")
    else:
        print("MAPE:\nN/A (No valid non-zero observations)\n")
        
    print(f"RMSE:\n{rmse:.4f}\n")
    
    print("Predefined MAPE Target:\n< 10%\n")
    
    if mape is not None:
        if mape < 10.0:
            print("Status:\nMEETS TARGET\n")
        else:
            print("Status:\nDOES NOT MEET TARGET\n")
    else:
        print("Status:\nINSUFFICIENT DATA\n")
        
    print("=" * 50)
    print("STORE-LEVEL RESULTS")
    print("=" * 50)
    
    # Calculate per-store metrics
    stores = test_data['store_id'].unique()
    for store_id in sorted(stores):
        store_mask = test_data['store_id'] == store_id
        train_store_mask = train_data['store_id'] == store_id
        
        y_test_store = y_test[store_mask]
        pred_store = predictions[store_mask]
        
        train_obs = train_store_mask.sum()
        val_obs = len(y_test_store)
        
        print(f"Store ID: {int(store_id)}")
        print(f"Training Observations: {train_obs}")
        print(f"Validation Observations: {val_obs}")
        
        if val_obs > 0:
            s_mape, s_zero, s_valid = calculate_mape(y_test_store, pred_store)
            s_rmse = calculate_rmse(y_test_store, pred_store)
            
            print(f"Zero-Demand Observations: {s_zero}")
            
            if s_mape is not None:
                print(f"MAPE: {s_mape:.2f}%")
            else:
                print("MAPE: N/A")
                
            print(f"RMSE: {s_rmse:.4f}")
            
            if s_mape is not None:
                status = "MEETS TARGET" if s_mape < 10.0 else "DOES NOT MEET TARGET"
            else:
                status = "INSUFFICIENT DATA"
                
            print(f"Status: {status}")
        else:
            print("Status: INSUFFICIENT DATA")
        print("-" * 30)

    # Prepare detailed results dataframe
    results_df = test_data.copy()
    results_df['actual_demand'] = y_test
    results_df['predicted_demand'] = predictions
    results_df['absolute_error'] = np.abs(results_df['actual_demand'] - results_df['predicted_demand'])
    
    # Calculate absolute percentage error (set to NaN where actual is 0)
    results_df['absolute_percentage_error'] = np.where(
        results_df['actual_demand'] != 0,
        (results_df['absolute_error'] / results_df['actual_demand']) * 100,
        np.nan
    )
    
    display_cols = ['transaction_date', 'store_id', 'inventory_id', 'actual_demand', 'predicted_demand', 'absolute_error', 'absolute_percentage_error']
    
    if args.show_sample:
        print("\n" + "=" * 50)
        print(f"SAMPLE PREDICTIONS (Top {args.show_sample})")
        print("=" * 50)
        sample_df = results_df[display_cols].head(args.show_sample)
        # Format the date nicely
        sample_df['transaction_date'] = sample_df['transaction_date'].dt.strftime('%Y-%m-%d')
        # Format numbers
        sample_df['predicted_demand'] = sample_df['predicted_demand'].round(2)
        sample_df['absolute_error'] = sample_df['absolute_error'].round(2)
        sample_df['absolute_percentage_error'] = sample_df['absolute_percentage_error'].round(2)
        
        # Rename columns for concise printing
        sample_df = sample_df.rename(columns={
            'transaction_date': 'Date',
            'store_id': 'Store',
            'inventory_id': 'Product',
            'actual_demand': 'Actual',
            'predicted_demand': 'Predicted',
            'absolute_error': 'Abs Error',
            'absolute_percentage_error': 'APE (%)'
        })
        print(sample_df.to_string(index=False))
        
    if args.output:
        results_df[display_cols].to_csv(args.output, index=False)
        print(f"\nRaw validation results saved to {args.output}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Tindahan Random Forest Model Validation Tool")
    parser.add_argument("--source", type=str, required=True, choices=["database"], 
                        help="Data source (must be 'database')")
    parser.add_argument("--show-sample", type=int, default=0, 
                        help="Display a sample of predictions (specify number of rows)")
    parser.add_argument("--output", type=str, default=None, 
                        help="Save raw validation results to CSV (e.g., validation_results.csv)")
    
    args = parser.parse_args()
    
    # We load dotenv here to check if DB settings are present before running
    # but the load_validation_data function handles it more robustly.
    validate_model(args)
