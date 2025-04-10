using Microsoft.AspNetCore.Http; // For IFormFile
using System.Collections.Generic;
using System.IO;
using System.Threading.Tasks;

namespace cxc_tool_asp.Services;

/// <summary>
/// Defines operations for interacting with the underlying storage (local file system or Azure Blob).
/// Paths are relative to the configured root (e.g., "Data/UserFolder/file.txt" or "Data2/users.csv").
/// </summary>
public interface IStorageService
{
    /// <summary>
    /// Saves a file stream to the specified relative path. Overwrites if exists.
    /// </summary>
    /// <param name="relativePath">The relative path within the storage container/directory.</param>
    /// <param name="stream">The stream containing the file content.</param>
    /// <returns>True if successful, false otherwise.</returns>
    Task<bool> SaveFileAsync(string relativePath, Stream stream);

    /// <summary>
    /// Saves an IFormFile to the specified relative path. Overwrites if exists.
    /// </summary>
    /// <param name="relativePath">The relative path within the storage container/directory.</param>
    /// <param name="formFile">The uploaded file.</param>
    /// <returns>True if successful, false otherwise.</returns>
    Task<bool> SaveFileAsync(string relativePath, IFormFile formFile);

    /// <summary>
    /// Reads the content of a file as a stream.
    /// </summary>
    /// <param name="relativePath">The relative path to the file.</param>
    /// <returns>A stream containing the file content, or null if the file doesn't exist.</returns>
    Task<Stream?> ReadFileAsStreamAsync(string relativePath);

    /// <summary>
    /// Reads the content of a file as text.
    /// </summary>
    /// <param name="relativePath">The relative path to the file.</param>
    /// <returns>The file content as a string, or null if the file doesn't exist.</returns>
    Task<string?> ReadFileAsTextAsync(string relativePath);

    /// <summary>
    /// Deletes a file.
    /// </summary>
    /// <param name="relativePath">The relative path to the file.</param>
    /// <returns>True if successful or file didn't exist, false on error.</returns>
    Task<bool> DeleteFileAsync(string relativePath);

    /// <summary>
    /// Checks if a file exists.
    /// </summary>
    /// <param name="relativePath">The relative path to the file.</param>
    /// <returns>True if the file exists, false otherwise.</returns>
    Task<bool> FileExistsAsync(string relativePath);

    /// <summary>
    /// Moves (renames) a file within the same storage context.
    /// </summary>
    /// <param name="sourceRelativePath">The current relative path of the file.</param>
    /// <param name="destinationRelativePath">The desired new relative path of the file.</param>
    /// <returns>True if successful, false otherwise (e.g., source doesn't exist, destination exists).</returns>
    Task<bool> MoveFileAsync(string sourceRelativePath, string destinationRelativePath);

    /// <summary>
    /// Lists files within a specified relative directory path. Non-recursive.
    /// </summary>
    /// <param name="relativeDirectoryPath">The relative path of the directory.</param>
    /// <returns>A list of filenames (not full paths) within the directory.</returns>
    Task<List<string>> ListFilesAsync(string relativeDirectoryPath);

    /// <summary>
    /// Lists subdirectories within a specified relative directory path. Non-recursive.
    /// </summary>
    /// <param name="relativeDirectoryPath">The relative path of the directory.</param>
    /// <returns>A list of subdirectory names (not full paths) within the directory.</returns>
    Task<List<string>> ListSubdirectoriesAsync(string relativeDirectoryPath);

    /// <summary>
    /// Deletes a directory and all its contents recursively. Use with caution.
    /// </summary>
    /// <param name="relativeDirectoryPath">The relative path of the directory to delete.</param>
    /// <returns>True if successful or directory didn't exist, false on error.</returns>
    Task<bool> DeleteDirectoryAsync(string relativeDirectoryPath);

    /// <summary>
    /// Gets the public URL for a file if applicable (mainly for Blob Storage).
    /// Returns null for local file system or if URL cannot be generated.
    /// </summary>
    /// <param name="relativePath">The relative path to the file.</param>
    /// <returns>The public URL or null.</returns>
    string? GetFileUrl(string relativePath);

    /// <summary>
    /// Copies a file from a source path (local file system) to a destination path in the storage.
    /// Used for seeding data like the default subjects.csv.
    /// </summary>
    /// <param name="localSourcePath">The full path to the local source file.</param>
    /// <param name="destinationRelativePath">The relative path in the storage.</param>
    /// <param name="overwrite">Whether to overwrite the destination if it exists.</param>
    /// <returns>True if successful, false otherwise.</returns>
    Task<bool> CopyLocalFileToStorageAsync(string localSourcePath, string destinationRelativePath, bool overwrite = false);
}
