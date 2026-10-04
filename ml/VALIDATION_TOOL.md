# Random Forest Validation Tool

This directory contains the `validate_random_forest.py` script, a robust and reusable validation tool designed to evaluate the Tindahan-Ecommerce localized demand forecasting model.

**IMPORTANT:** This tool is strictly for validating the performance of the Random Forest model on a *local, isolated snapshot* of the final cloud production database. It does NOT generate the final capstone validation report, nor does it connect to the production cloud environment.

## 1. Validation Purpose

The purpose of this script is to rigorously validate the accuracy of the historical demand predictions using the Random Forest algorithm. This helps us ensure that the final system can handle demand forecasting dynamically and verifies our results against the predefined capstone requirement (MAPE < 10%).

The validation script replicates the production feature engineering pipeline (from `random_forest_demand.py`), guarantees chronological holdout to prevent temporal leakage, and computes the Mean Absolute Percentage Error (MAPE) and Root Mean Squared Error (RMSE).

## 2. Required Database Schema

The tool relies strictly on the actual historical sales view used by the production ML model:
- `ml_historical_sales_view` (derived from actual sales/transactions data)
- Required fields: `transaction_date`, `store_id`, `inventory_id`, `total_daily_quantity`, `season_category`, `holiday_event`.

## 3. Required Environment Variables

To protect production credentials and ensure the tool is isolated, the script reads configuration from a dedicated environment file.

You MUST create a `.env.validation` file at the root of the project (or inside the `ml/` directory) containing your local MySQL/MariaDB database credentials:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tindahan_ecommerce_validation
DB_USERNAME=root
DB_PASSWORD=
```

## 4. How to Create and Use the Local Validation Database

1. Export a complete snapshot of the final cloud production database.
2. Open your local MySQL/MariaDB instance.
3. Create a new, separate database (e.g., `CREATE DATABASE tindahan_ecommerce_validation;`).
4. Import the snapshot into this new database.
5. Update `.env.validation` to point to this local database.

**The script treats this database as read-only. It performs NO inserts, updates, or schema migrations.**

## 5. How to Run Database Validation

Activate your Python environment and run:

```bash
cd ml/
python validate_random_forest.py --source database
```

This will output the training and validation counts, overall MAPE and RMSE, and the validation status per store.

## 6. How to Export Raw CSV Results

You can export a detailed CSV file containing raw validation results, including actual vs. predicted demands and absolute errors:

```bash
python validate_random_forest.py --source database --output validation_results.csv
```

To display a small sample in your terminal without exporting:

```bash
python validate_random_forest.py --source database --show-sample 20
```

## 7. How the Chronological Split Works

Instead of shuffling data randomly (which leaks future events into the past), the validator sorts the dataset chronologically by `transaction_date`. 
- **Training Period:** The earliest 80% of the dataset.
- **Validation Period:** The latest 20% of the dataset.

This closely mimics the real-world scenario of training on past data to predict future demand.

## 8. Zero-Demand Handling

MAPE calculation formula divides the absolute error by the actual demand. If the actual demand is 0, the equation is undefined (division by zero).
- The validator detects zero-demand actual observations.
- It safely excludes them from the MAPE calculation, allowing the validation to continue without crashing.
- The terminal output transparently reports the exact number of Zero-Demand Observations discarded and the number of valid non-zero observations used for the MAPE calculation.
- RMSE calculations are unaffected and use all validation data.

## 9. MAPE Calculation

Mean Absolute Percentage Error (MAPE) is calculated as:
`MAPE = (100% / n) * Σ |(Actual - Predicted) / Actual|`

The target criteria is **MAPE < 10%**.

## 10. RMSE Calculation

Root Mean Squared Error (RMSE) is calculated as:
`RMSE = sqrt((1/n) * Σ(Predicted - Actual)^2)`

## 11. Important Constraints

- **No Data Fabrication:** The validator uses actual `ml_historical_sales_view` data.
- **No Production Modification:** The script is strictly read-only.
- **Predefined Targets:** The validator honestly reports if the model FAILS to meet the 10% MAPE target.
- **Final Results:** The terminal and CSV output from this script will serve as the factual evidence for the final Results and Discussion chapter. Do NOT generate the final report until the final cloud database snapshot is obtained.
