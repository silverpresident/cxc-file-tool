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

## User Features

### Home Page
- Displays a welcome message.
- Provides access to other features via the navigation bar.

### Upload Files
1.  Click the "Upload" link in the navigation bar.
2.  **To Upload:**
    *   Click the "Choose file" button and select the file you want to upload from your device.
    *   Click the "Upload" button.
    *   A success or error message will appear at the top.
3.  **Uploaded Files List:**
    *   Files you have uploaded but not yet renamed are listed on the right side.
    *   You can delete an uploaded file by clicking the red trash icon (<i class="bi bi-trash"></i>) next to it. Confirm the deletion when prompted.
4.  Once files are uploaded, proceed to the "Rename & Download" page.

### Rename & Download Files
1.  Click the "Rename & Download" link in the navigation bar.
2.  **Select Files:** Check the boxes next to the files you want to process (rename) together for a single candidate and subject. You can use the "Select/Deselect All" button.
3.  **Enter Renaming Details:**
    *   **Subject:** Select the correct subject from the dropdown list.
    *   **Centre Number:** This usually defaults to `100111`. Confirm if it's correct.
    *   **Candidate:** Start typing the candidate's name or 10-digit registration number. Select the correct candidate from the list that appears, or ensure you have typed the full 10-digit registration number correctly.
    *   **Document Type:** Select the type for the *selected* files:
        *   `Cover Sheet (CS)`: For the SBA cover sheet.
        *   `Mark Scheme (MS)`: For the SBA mark scheme.
        *   `Project File(s) (-1, -2, ...)`: For the actual SBA project files. If you select multiple project files, they will be numbered sequentially (e.g., `-1`, `-2`).
4.  **Rename:** Click the "Rename Selected Files" button. The page will refresh, and the renamed files should appear in the "Processed Files" section (Note: This section needs further implementation). Success or error messages will appear at the top.
5.  **Download/Delete Processed Files:** (Functionality details TBD in the "Processed Files" section)
    *   **Download Individual:** Click the download icon next to a specific renamed file.
    *   **Download All:** Click the "Download All (.zip)" button to get a zip archive of all files currently in your folder.
    *   **Download by Subject:** Select a subject from a dropdown (TBD) and click "Download by Subject (.zip)" to get a zip archive containing only files matching that subject code.
    *   **Delete:** Click the delete icon next to a specific renamed file.

## Administrator Features

Administrators have access to all user features plus the following sections under the "Admin" menu:

### Manage Users
- **View:** Lists all registered users, their system-generated folder name, and admin status.
- **Add:** Click "Add New User". Fill in the Display Name, Password (and confirm), and check "Is Administrator?" if needed. Click "Add User". A unique folder name is generated automatically.
- **Edit:** Click the "Edit" button next to a user. Modify the Display Name or Admin status. To change the password, enter a new password in both password fields. Leave password fields blank to keep the existing password. Click "Save Changes".
- **Delete:** Click the "Delete" button next to a user. Confirm the deletion. **Note:** This only deletes the user account, not their uploaded files.

### Manage Candidates
- **View:** Lists candidates for the current year (`{YEAR}cand.csv`).
- **Add:** Click "Add New Candidate". Fill in the 10-digit Registration No, Name, and optional Class/Subjects. Click "Add Candidate".
- **Edit:** Click "Edit" next to a candidate. Modify Name, Class, or Subjects. Registration No cannot be changed. Click "Save Changes".
- **Delete:** Click "Delete" next to a candidate. Confirm deletion.
- **Upload CSV List:** Click "Upload CSV List". Choose a CSV file containing candidate data. The system will attempt to import candidates based on common headers (e.g., `name`, `registrationno`, `class`, `subjects`). **This replaces the entire existing list for the current year.**
- **Download CSV List:** Click "Download CSV List" to get the current candidate list as a CSV file.
- **Delete All Candidates:** Click "Delete All Candidates". Confirm the deletion. **This removes all candidates for the current year.**

### Manage Subjects
- **View:** Lists all subjects (`subjects.csv`).
- **Add:** Click "Add New Subject". Fill in the 6-digit Subject Code, Name, and select the Level (CSEC/CAPE). Click "Add Subject".
- **Edit:** Click "Edit" next to a subject. Modify Name or Level. Subject Code cannot be changed. Click "Save Changes".
- **Delete:** Click "Delete" next to a subject. Confirm deletion.
- **Upload CSV List:** Click "Upload CSV List". Choose a CSV file containing subject data (headers like `name`, `subjectcode`, `level`). **This replaces the entire existing list.**
- **Download CSV List:** Click "Download CSV List" to get the current subject list as a CSV file.
- **Delete All Subjects:** Click "Delete All Subjects". Confirm the deletion. **This removes all subjects.**

### Manage All Files
- **View:** Lists all files currently stored in *all* user folders within the `Data` directory. Shows the user folder and the filename.
- **Download:** Click the "Download" button next to any file to download it directly.
- **Delete:** Click the "Delete" button next to any file. Confirm the deletion. **This permanently deletes the file.**

## Troubleshooting

- **Login Failed:** Double-check your Display Name and Password. Contact an administrator if you have forgotten your credentials.
- **File Upload Failed:** Ensure you selected a file. Check for any error messages displayed.
- **Renaming Failed:** Ensure all parameters (Subject, Candidate, Doc Type) are selected/entered correctly. Check the candidate registration number format (10 digits). Ensure the target filename doesn't already exist.
- **Download Failed:** The file may have been deleted or an error occurred during zip creation. Try again or contact an administrator.

*(This manual is preliminary and may be updated as features are refined.)*
