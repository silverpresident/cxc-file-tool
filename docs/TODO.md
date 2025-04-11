# TODO List - Shane's CXC File Tool

This list tracks pending tasks and potential improvements for the application.

## High Priority / Core Functionality

*   [x] **Rename Page - UI Refinement:**
    *   Ensure dynamic updates (moving files between lists after AJAX rename/delete) are fully robust.
    *   Consider adding visual feedback during AJAX operations (e.g., disabling buttons, showing spinners).
*   [x] ~~**Initial Admin User Creation:** Implement a mechanism (e.g., command-line tool, first-run setup page, configuration setting) to create the initial administrator account, as manual CSV editing is not ideal.~~ (Done via `--create-admin` flag)

## Medium Priority / Refinements

*   [x] **UI/UX Polish:**
    *   Review all views for consistent styling and layout according to Bootstrap 5 best practices.
    *   Improve mobile responsiveness, especially for tables in Admin views and the Rename/Upload page layouts.
    *   Add loading indicators for potentially long-running operations (like zip creation, large CSV uploads/imports, listing all admin files).
    *   Refine success/error message display (currently prepended, consider dedicated area or toasts).
*   [ ] **Error Handling:**
    *   Implement more specific exception handling and user-friendly messages in controllers and services.
    *   Configure global exception handling middleware in `Program.cs` to provide user-friendly error pages for unhandled exceptions.
    *   Improve logging detail and structure (consider structured logging like Serilog).
*   [x] ~~**File Validation:** Add server-side validation in `UploadController` for allowed file types (e.g., PDF, DOCX, common image types) and maximum file size.~~ (Done)
*   [ ] **Admin User File Deletion:** Decide if deleting a user should also delete their associated storage folder (`IStorageService.DeleteDirectoryAsync`). Implement if required. Currently, only the user record is deleted.
*   [ ] **Client-Side Validation:** Add client-side validation for file type/size on the Upload page to provide faster feedback.

## Low Priority / Future Enhancements

*   [ ] **Testing:**
    *   Write unit tests for services, especially focusing on CSV parsing/writing, storage interactions, and logic like folder name generation or renaming conventions.
    *   Write integration tests for controller actions.
*   [ ] **Configuration:** Move hardcoded values (like default Centre Number `100111`, MaxFileSize, AllowedExtensions) to `appsettings.json`.
*   [ ] **Refactoring:** Extract common CRUD logic from `AdminController` if it becomes too large. Review `IStorageService` usage and path handling.
*   [ ] **Security Hardening:** Review input sanitization, add checks against excessively large file uploads (consider `RequestSizeLimit` attribute), potentially implement rate limiting. Review file download security (ensure users can only access their own files, except admin).
*   [ ] **Database Migration:** Consider migrating from CSV files to a proper database (e.g., SQLite, SQL Server, PostgreSQL) for improved performance, scalability, and data integrity.
*   [ ] **Rename Page - Project File Numbering:** The current logic assigns "-1" to any file marked as "Project". Enhance this to check existing processed files for the same candidate/subject and assign the next available number (e.g., "-2", "-3").

*(This list should be reviewed and updated regularly.)*
