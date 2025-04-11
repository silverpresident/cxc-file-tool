# User Manual - Shane's CXC File Tool

## Introduction

Welcome to Shane's CXC File Tool! This application helps manage Caribbean Examinations Council (CXC) School-Based Assessment (SBA) files, including cover sheets, mark schemes, and project files. It allows users to upload files and rename them according to the official CXC naming convention. Administrators have additional privileges to manage users, candidate lists, subject lists, and all uploaded files.

## Getting Started

### Logging In
1.  Navigate to the application's URL.
2.  Click the "Login" link in the top-right corner.
3.  Enter your assigned **Display Name** and **Password**.
4.  Optionally, check "Remember me?" to stay logged in across browser sessions.
5.  Click the "Log in" button.

Upon successful login, you will be redirected to the Home page.

**Note:** If this is the first time running the application, an administrator must first create the initial admin account using the command line: `dotnet run -- --create-admin` in the `cxc-tool-asp` directory.

## User Features

### Home Page
- Displays a welcome message.
- Provides access to other features via the navigation bar.

### Upload Files
1.  Click the "Upload" link in the navigation bar.
2.  **To Upload:**
    *   Drag and drop one or more files onto the designated drop zone area.
    *   OR click the "Choose Files..." button and select one or more files.
    *   Uploads start automatically via AJAX. A progress bar appears for each file.
    *   Success or error messages appear at the top and disappear automatically after 30 seconds.
3.  **Uploaded Files List:**
    *   Files you have uploaded but not yet renamed are listed on the right side. The list updates automatically after successful uploads.
    *   You can delete an uploaded file by clicking the red trash icon (<i class="bi bi-trash"></i>) next to it. This happens via AJAX. Confirm the deletion when prompted.
4.  Once files are uploaded, proceed to the "Rename & Download" page.

### Rename & Download Files
1.  Click the "Rename & Download" link in the navigation bar.
2.  The page is split into "Rename Unprocessed File" and "Manage Processed Files".
3.  **Rename Unprocessed File:**
    *   **Select File:** Choose *one* file to rename using the radio buttons in the "Unprocessed Files" list. You can click the "View" icon (<i class="bi bi-eye"></i>) to open the file in a new tab.
    *   **Enter Renaming Details:**
        *   **Subject:** Select the correct subject (uses 8-digit code internally).
        *   **Centre Number:** Confirm the 6-digit number (defaults to `100111`).
        *   **Candidate Code:** Enter the correct 4-digit candidate code. You can type a name to filter the datalist or enter the code directly.
        *   **Document Type:** Select `Cover Sheet (CS)`, `Mark Scheme (MS)`, or `Project File (-1)`.
    *   **Rename:** Click the "Rename Selected File" button. This uses AJAX.
        *   On success, a message appears (lasts 60 seconds), the file is removed from the "Unprocessed" list, and added to the "Processed" list. The form resets.
        *   On failure, an error message appears (lasts 30 seconds).
4.  **Manage Processed Files:**
    *   This section lists files that already match the naming convention.
    *   **View:** Click the "View" icon (<i class="bi bi-eye"></i>) to open the file in a new tab.
    *   **Download Individual:** Click the "Download" icon (<i class="bi bi-download"></i>) next to a file.
    *   **Delete:** Click the "Delete" icon (<i class="bi bi-trash"></i>) next to a file. This uses AJAX. Confirm the deletion.
    *   **Download All:** Click "Download All (.zip)" to get a zip archive of all files currently in your folder (both processed and unprocessed).
    *   **Download by Subject:** Click the "Download by Subject" dropdown, choose a subject, and a zip archive containing only processed files matching that subject code will be downloaded.

## Administrator Features

Administrators have access to all user features plus the following sections under the "Admin" menu:

### Manage Users
- **View:** Lists all registered users, their system-generated folder name, and admin status.
- **Add:** Click "Add New User". Fill in the Display Name, Password (and confirm), and check "Is Administrator?" if needed. Click "Add User".
- **Edit:** Click "Edit". Modify Display Name or Admin status. Enter a new password to change it (leave blank to keep current). Click "Save Changes".
- **Delete:** Click "Delete". Confirm deletion. **Note:** Does not delete user's files.

### Manage Candidates (`Data/{YEAR}cand.csv`)
- **View:** Lists candidates for the current year.
- **Add:** Click "Add New Candidate". Fill in 10-digit RegNo, Name, optional Class, Exam, Subjects. Click "Add Candidate".
- **Edit:** Click "Edit". Modify Name, Class, Exam, Subjects. RegNo cannot be changed. Click "Save Changes".
- **Delete:** Click "Delete". Confirm deletion.
- **Upload CSV List:** Replaces the current year's list. Uses flexible header mapping (e.g., `name`/`partyname`, `cxcregistrationno`/`cxc_candidate_no`, etc.).
- **Download CSV List:** Downloads the current list.
- **Delete All Candidates:** Removes all candidates for the current year.

### Manage Subjects (`Data2/subjects.csv`)
- **View:** Lists all subjects.
- **Add:** Click "Add New Subject". Fill in 8-digit Subject Code, Name, Level. Click "Add Subject".
- **Edit:** Click "Edit". Modify Name or Level. Subject Code cannot be changed. Click "Save Changes".
- **Delete:** Click "Delete". Confirm deletion.
- **Upload CSV List:** Replaces the entire list. Uses flexible header mapping.
- **Download CSV List:** Downloads the current list.
- **Delete All Subjects:** Removes all subjects.
- **Import Defaults:** Overwrites the current subject list in storage with the default list included with the application. Use this if the list becomes corrupted or needs resetting.

### Manage All Files (`Data/` subfolders)
- **View:** Lists all files across all user folders.
- **Download:** Click "Download" next to any file.
- **Delete:** Click "Delete" next to any file. Confirm deletion.

## Troubleshooting

- **Login Failed:** Double-check Display Name/Password. Use the `--create-admin` command if no admin exists.
- **File Upload Failed:** Check file type/size against limits (PDF, DOCX, JPG, PNG, max 10MB). Check error messages.
- **Renaming Failed:** Ensure a file is selected, all parameters are filled, candidate code is 4 digits, centre number is 6 digits. Check if the target filename already exists. Check error messages.
- **Download Failed:** File may have been deleted or an error occurred. Check error messages.

*(This manual is preliminary and may be updated as features are refined.)*
