# TODO List - Shane's CXC File Tool

This list tracks pending tasks and potential improvements for the application.

## High Priority / Core Functionality

*   [ ] **Rename Page - Processed Files Display:**
    *   Implement the section in `Views/Rename/Index.cshtml` to list files after they have been successfully renamed.
    *   Enable the "Download All", "Download by Subject", and individual file download/delete buttons for these processed files.
    *   Consider dynamically updating this list via AJAX after a rename operation for better UX.
*   [ ] **Initial Admin User Creation:** Implement a mechanism (e.g., command-line tool, first-run setup page, configuration setting) to create the initial administrator account, as manual CSV editing is not ideal.

## Medium Priority / Refinements

*   [ ] **UI/UX Polish:**
    *   Review all views for consistent styling and layout according to Bootstrap 5 best practices.
    *   Improve mobile responsiveness, especially for tables in Admin views and the Rename page layout.
    *   Add loading indicators for long-running operations (like zip creation or large CSV uploads).
    *   Refine success/error message display (e.g., using toast notifications instead of TempData alerts).
*   [ ] **Error Handling:**
    *   Implement more specific exception handling in controllers and services.
    *   Configure global exception handling middleware in `Program.cs` to provide user-friendly error pages for unhandled exceptions.
    *   Improve logging detail and structure.
*   [ ] **File Validation:** Add server-side validation in `UploadController` for allowed file types (e.g., PDF, DOCX, common image types) and maximum file size.
*   [ ] **Admin File Deletion:** Clarify if deleting a user should also delete their associated files/folder. If so, implement this in `UserService.DeleteUserAsync` or provide a separate admin function. Currently, only the user record is deleted.

## Low Priority / Future Enhancements

*   [ ] **Testing:**
    *   Write unit tests for services, especially focusing on CSV parsing/writing, file operations, and logic like folder name generation or renaming conventions.
    *   Write integration tests for controller actions.
*   [ ] **Configuration:** Move hardcoded values (like default Centre Number `100111`) to `appsettings.json`.
*   [ ] **Refactoring:** Extract common CRUD logic from `AdminController` if it becomes too large.
*   [ ] **Security Hardening:** Review input sanitization, add checks against excessively large file uploads, potentially implement rate limiting.
*   [ ] **Database Migration:** Consider migrating from CSV files to a proper database (e.g., SQLite, SQL Server, PostgreSQL) for improved performance, scalability, and data integrity.

*(This list should be reviewed and updated regularly.)*
