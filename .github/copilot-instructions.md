# API Repository - Copilot Instructions

## 🔌 Repository Focus: Backend API Development

This repository contains the **vanilla PHP backend API** for Netanel Klein's portfolio system.

## 🤖 Mandatory Workflow

**Before Starting Any Work:**
1. **MANDATORY**: Read the `.ai-context.md` file first to understand current project state, completed features, and requirements
2. Create a new feature branch from main using descriptive names like `feature/contact-form-api` or `fix/database-connection`
3. Use the run_in_terminal tool to execute git commands like `git checkout -b feature/feature-name`

**After Completing Work:**
1. **MANDATORY**: Update the `.ai-context.md` file to reflect changes made, new features implemented, or issues resolved
2. Commit changes with meaningful messages and proper attribution
3. **User Approval Required**: Always ask the user if they are satisfied with the implementation before committing

**Git Configuration**: Configure git as GitHub Copilot before committing:
```bash
git config user.name "GitHub Copilot"
```
Use semantic commit messages (feat:, fix:, docs:, refactor:, etc.) and always sign commits with:
`Signed-off-by: GitHub Copilot`

## 🎯 Backend-Specific Guidelines

**Resource Efficiency**: Optimize for 1 CPU + 6GB RAM OCI VM
- Use vanilla PHP (no Composer) for maximum performance
- Implement efficient database queries with PDO
- Minimize memory usage and optimize for ARM architecture

**Security First**: 
- Implement proper input validation and sanitization
- Use prepared statements for all database queries
- Add rate limiting and anti-spam protection
- Secure admin endpoints with authentication

**API Design**:
- Follow RESTful conventions
- Implement proper HTTP status codes
- Provide clear error messages
- Add comprehensive logging

**Database Integration**:
- Use MySQL with PDO for database connections
- Implement proper error handling
- Design normalized schema
- Add migration system for schema changes

**Documentation**:
- Document all API endpoints
- Include request/response examples
- Add security considerations
- Maintain deployment instructions

This is a **public repository** designed to showcase backend development skills to potential employers while serving the portfolio system efficiently.
