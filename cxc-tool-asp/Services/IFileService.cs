using Microsoft.AspNetCore.Mvc; // Required for FileStreamResult

namespace cxc_tool_asp.Services;

/// <summary>
/// Interface for managing file operations within user-specific folders.
/// </summary>
public interface IFileService
{
    /// <summary>
    /// Saves an uploaded file to the specified user's folder.
    /// Ensures the user's folder exists.
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <param name="file">The uploaded file.</param>
    /// <param name="desiredFileName">Optional: The desired name for the saved file (without extension). If null, uses the original filename.</param>
    /// <returns>The final path of the saved file relative to the web root, or null if saving failed.</returns>
    Task<string?> SaveFileAsync(string userFolderName, IFormFile file, string? desiredFileName = null);

    /// <summary>
    /// Retrieves a list of files within a user's folder.
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <returns>A list of file names (including extension) within the user's folder.</returns>
    Task<List<string>> GetFilesAsync(string userFolderName);

    /// <summary>
    /// Gets the full path to a specific file within a user's folder.
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <param name="fileName">The name of the file (including extension).</param>
    /// <returns>The full file path, or null if the file or folder doesn't exist.</returns>
    string? GetFilePath(string userFolderName, string fileName);

    /// <summary>
    /// Deletes a specific file from a user's folder.
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <param name="fileName">The name of the file to delete (including extension).</param>
    /// <returns>True if deletion was successful or file didn't exist; false if an error occurred.</returns>
    Task<bool> DeleteFileAsync(string userFolderName, string fileName);

    /// <summary>
    /// Renames a file within a user's folder according to the specified convention.
    /// {cxc_registration_no(10 digit)}{cxc_subject_code}{document-identifier}{file-extension}
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <param name="originalFileName">The current name of the file.</param>
    /// <param name="registrationNo">The 10-digit CXC registration number.</param>
    /// <param name="subjectCode">The 6-digit CXC subject code.</param>
    /// <param name="docIdentifier">The document identifier ("CS", "MS", or "-N" for projects).</param>
    /// <returns>The new file name if successful, otherwise null.</returns>
    Task<string?> RenameFileAsync(string userFolderName, string originalFileName, string registrationNo, string subjectCode, string docIdentifier);

    /// <summary>
    /// Deletes all files within a specific user's folder.
    /// </summary>
    /// <param name="userFolderName">The unique folder name of the user.</param>
    /// <returns>True if deletion was successful; false otherwise.</returns>
    Task<bool> DeleteUserFilesAsync(string userFolderName);

     /// <summary>
    /// Gets a list of all files across all user folders within the main Data directory.
    /// Used by Admin.
    /// </summary>
    /// <returns>A list of tuples containing (UserFolderName, FileName).</returns>
    Task<List<(string UserFolder, string FileName)>> GetAllFilesInDataDirectoryAsync();

    /// <summary>
    /// Deletes a specific file identified by user folder and filename. Used by Admin.
    /// </summary>
    /// <param name="userFolderName">The folder containing the file.</param>
    /// <param name="fileName">The name of the file to delete.</param>
    /// <returns>True if successful, false otherwise.</returns>
    Task<bool> DeleteSpecificFileAsync(string userFolderName, string fileName);

    /// <summary>
    /// Creates a FileStreamResult for downloading a specific file.
    /// </summary>
    /// <param name="userFolderName">The user's folder name.</param>
    /// <param name="fileName">The name of the file to download.</param>
    /// <returns>A FileStreamResult for the requested file, or null if the file doesn't exist.</returns>
    Task<FileStreamResult?> GetFileDownloadAsync(string userFolderName, string fileName);

     /// <summary>
    /// Creates a zip archive containing specified files from a user's folder.
    /// </summary>
    /// <param name="userFolderName">The user's folder name.</param>
    /// <param name="filesToZip">A list of filenames to include in the zip.</param>
    /// <param name="zipFileName">The desired name for the zip file (without .zip extension).</param>
    /// <returns>A memory stream containing the zipped files, or null if an error occurs.</returns>
    Task<MemoryStream?> CreateZipArchiveAsync(string userFolderName, List<string> filesToZip, string zipFileName);
}
