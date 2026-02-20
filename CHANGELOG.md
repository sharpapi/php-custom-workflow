# Changelog

## 1.0.0 - 2026-02-20

- Initial release
- `listWorkflows()` — list user's workflows, paginated
- `describeWorkflow()` — get workflow metadata and parameter schema
- `executeWorkflow()` — execute JSON or form-data workflows
- `validateAndExecute()` — client-side validation before execution
- `PayloadValidator` — mirrors server-side validation for JSON and form-data modes
- DTOs: `WorkflowDefinition`, `WorkflowParam`, `WorkflowListResult`
- Enums: `InputMode`, `ParamType`
