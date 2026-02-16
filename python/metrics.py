from __future__ import annotations

import argparse
import csv
from typing import List, Tuple

from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score


def load_labels_from_csv(path: str) -> Tuple[List[int], List[int]]:
    y_true: List[int] = []
    y_pred: List[int] = []
    with open(path, newline="", encoding="utf-8") as fh:
        reader = csv.DictReader(fh)
        if "y_true" not in reader.fieldnames or "y_pred" not in reader.fieldnames:
            raise ValueError("CSV must have headers: y_true,y_pred")
        for row in reader:
            y_true.append(int(row["y_true"]))
            y_pred.append(int(row["y_pred"]))
    return y_true, y_pred


def compute_metrics(y_true: List[int], y_pred: List[int], average: str) -> dict:
    return {
        "accuracy": accuracy_score(y_true, y_pred),
        "precision": precision_score(y_true, y_pred, average=average, zero_division=0),
        "recall": recall_score(y_true, y_pred, average=average, zero_division=0),
        "f1": f1_score(y_true, y_pred, average=average, zero_division=0),
    }


def main() -> None:
    parser = argparse.ArgumentParser(description="Compute accuracy/precision/recall/F1 from labels")
    parser.add_argument("--csv", help="CSV with headers y_true,y_pred", default="")
    parser.add_argument("--average", help="macro|micro|weighted", default="macro")
    args = parser.parse_args()

    if args.csv:
        y_true, y_pred = load_labels_from_csv(args.csv)
    else:
        # Example data; replace with your own labels or use --csv
        y_true = [1, 1, 1, 0, 0, 0, 1, 0, 1, 0]
        y_pred = [1, 1, 0, 0, 0, 0, 1, 0, 1, 1]

    metrics = compute_metrics(y_true, y_pred, args.average)

    print(f"Accuracy:  {metrics['accuracy'] * 100:.1f}%")
    print(f"Precision: {metrics['precision'] * 100:.1f}%")
    print(f"Recall:    {metrics['recall'] * 100:.1f}%")
    print(f"F1-score:  {metrics['f1'] * 100:.1f}%")


if __name__ == "__main__":
    main()
