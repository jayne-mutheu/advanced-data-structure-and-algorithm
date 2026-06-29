# Mobile Money Payment Transaction System - DSA Evaluation

This project is a design, analysis, and evaluation of different Data Structures and Algorithms (DSA) applied to a Mobile Money Payment Transaction System. It strictly follows the requirements of the BIT 4105 Advanced Data Structures course, analyzing system behavior at two distinct transaction volume levels: **≤ 1,000 transactions** and **10,000+ transactions**.

---

## 📑 Table of Contents
1. [Project Overview](#project-overview)
2. [Small-Scale System (≤ 1,000 Transactions)](#small-scale-system--1000-transactions)
    - [Implementation Details](#implementation-details)
    - [Theoretical Alternatives (Linked List, Stack, Queue)](#theoretical-alternatives)
3. [Medium/Large-Scale System (10,000+ Transactions)](#mediumlarge-scale-system-10000-transactions)
    - [Implementation Details](#implementation-details-1)
    - [Synchronized Data Structures](#synchronized-data-structures)
4. [Complexity Analysis Summary](#complexity-analysis-summary)

---

## 1. Project Overview
The objective is to manage payment transactions efficiently as the data scales. 
* **Level 1 (≤ 1,000):** Focuses on simplicity and ease of implementation. A simple array is sufficient, but we analyze how stacks, queues, and linked lists could also fit.
* **Level 2 (10,000+):** At this scale, linear operations bottleneck the system. We transition to hashing, balancing, and priority queues to maintain performance.

---

## 2. Small-Scale System (≤ 1,000 Transactions)

For small-scale operations (like a small retail shop or a localized event), memory is not an issue, and $O(n)$ time complexities are executed in milliseconds.

### Implementation Details
The baseline implementation uses a **Dynamic Array (`MainArray`)**.

* **Insert Operation (`InsertSmallTransaction`):** Transactions are appended to the end of the array. This is an $O(1)$ operation.
* **Search Operation (`LinearSearchTransaction`):** Finding a transaction by its ID requires iterating through the array sequentially. This is an $O(n)$ operation.

```mermaid
graph LR
    A[Incoming Transaction] -->|Append O1| B[(MainArray: Index 0, 1, 2... n)]
    C[Search Request] -->|Iterate On| B
