# TalaKlase Analytics Architecture

## Purpose

Analytics is a read-oriented SIS dashboard that presents institutional KPIs and analytical views without moving business logic into the presentation layer.

## Layers

Browser
  |
pages/analytics.php
  |
assets/js/analytics.js
  |
includes/analytics_controller.php
  |
Database

The controller prepares the data required by the view. The page renders the dashboard. JavaScript handles browser-side tab and interaction behavior. CSS controls the visual system.

## KPI Model

The executive summary uses four primary KPI groups:

- Student Population
- Section Coverage
- Faculty Capacity
- Teaching Capacity

KPIs must represent reliable current-scope measures. Do not display invented targets or percentage deltas without a trustworthy comparison source.

## Visualization Rules

- Trend over time: line visualization.
- Composition: donut or pie visualization.
- Category comparison: horizontal or vertical bars.
- Exact operational records: lists or tables.
- Actionable student attention: compact drill-down list.

## Output Hygiene

Analytics output must remain UTF-8 clean. Action-link symbols such as arrows and middle dots must not be introduced as mojibake. Visual inspection is part of Analytics QA because syntax validation alone cannot identify encoding artifacts.
