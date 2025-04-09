# API Documentation - Shane's CXC File Tool

This document outlines the controllers and services within the application.

## Controllers

### `AccountController`
- Handles user login and logout processes.
- **Actions:**
    - `Login` (GET): Displays the login form.
    - `Login` (POST): Validates credentials, creates authentication cookie, and redirects.
    - `Logout` (POST): Signs the user out and redirects.
    - `AccessDenied` (GET): Displays the access denied page.

### `AdminController`
- Provides administrative functionalities. Requires "Admin" role authorization.
- **Actions:**
    - `Users` (GET): Displays the user management page.
    - `AddUser` (GET): Displays the form to add a new user.
    - `AddUser` (POST): Processes the addition of a new user.
    - `EditUser` (GET): Displays the form to edit an existing user.
    - `EditUser` (POST): Processes the update of an existing user.
    - `DeleteUser` (POST): Deletes a specified user.
    - `Candidates` (GET): Displays the candidate management page.
    - `AddCandidate` (GET): Displays the form to add a new candidate.
    - `AddCandidate` (POST): Processes the addition of a new candidate.
    - `EditCandidate` (GET): Displays the form to edit an existing candidate.
    - `EditCandidate` (POST): Processes the update of an existing candidate.
    - `DeleteCandidate` (POST): Deletes a specified candidate.
    - `DeleteAllCandidates` (POST): Deletes all candidates for the current year.
    - `UploadCandidates` (POST): Processes the upload of a candidate CSV file.
    - `DownloadCandidates` (GET): Provides the current candidate list as a CSV download.
    - `Subjects` (GET): Displays the subject management page.
    - `AddSubject` (GET): Displays the form to add a new subject.
    - `AddSubject` (POST): Processes the addition of a new subject.
    - `EditSubject` (GET): Displays the form to edit an existing subject.
    - `EditSubject` (POST): Processes the update of an existing subject.
    - `DeleteSubject` (POST): Deletes a specified subject.
    - `DeleteAllSubjects` (POST): Deletes all subjects.
    - `UploadSubjects` (POST): Processes the upload of a subject CSV file.
    - `DownloadSubjects` (GET): Provides the current subject list as a CSV download.
    - `Files` (GET): Displays a list of all files across all user folders.
    - `DeleteFile` (POST): Deletes a specific file from a specific user folder.
    - `DownloadFile` (GET): Provides a specific file from a specific user folder for download.

### `HomeController`
- Handles the main landing page after login. Requires authentication.
- **Actions:**
    - `Index` (GET): Displays the welcome page.
    - `Error` (GET): Displays the error page (allows anonymous access).

### `UploadController`
- Handles the initial file upload process for authenticated users.
- **Actions:**
    - `Index` (GET): Displays the upload form and lists currently uploaded (unprocessed) files for the user.
    - `UploadFile` (POST): Processes the upload of a single file.
    - `DeleteUploadedFile` (POST): Deletes a specific file from the user's folder (typically before renaming).

### `RenameController`
- Handles the file renaming and downloading process for authenticated users.
- **Actions:**
    - `Index` (GET): Displays the renaming interface, listing user's files and providing renaming parameters.
    - `RenameFiles` (POST): Processes the renaming of selected files based on provided parameters.
    - `DownloadFile` (GET): Provides a specific file from the user's folder for download.
    - `DownloadAllFiles` (GET): Provides a zip archive of all files in the user's folder.
    - `DownloadFilesBySubject` (GET): Provides a zip archive of files matching a specific subject code in the user's folder.
    - `DeleteFile` (POST): Deletes a specific file from the user's folder.

## Services

### `IUserService` / `UserService`
- Manages user data stored in `Data2/users.csv`.
- Handles CRUD operations, password validation (BCrypt hashing), and unique folder name generation.
- Uses `CsvHelper` for file interaction.
- Implements basic caching and file locking.

### `ICandidateService` / `CandidateService`
- Manages candidate data stored in `Data/{YEAR}cand.csv`.
- Handles CRUD operations, CSV import/export.
- Uses `CsvHelper` for file interaction.
- Implements file locking.

### `ISubjectService` / `SubjectService`
- Manages subject data stored in `Data/subjects.csv`.
- Handles CRUD operations, CSV import/export.
- Uses `CsvHelper` for file interaction.
- Implements file locking.

### `IFileService` / `FileService`
- Manages file system operations within user-specific subfolders in the `Data` directory.
- Handles saving uploads, listing files, deleting files, renaming files according to convention, creating zip archives, and providing files for download.
- Includes basic path sanitization.
- Uses `MimeTypeMapOfficial` for MIME type detection during download.

*(This documentation is preliminary and should be expanded with more detail on parameters, return types, and error handling.)*
