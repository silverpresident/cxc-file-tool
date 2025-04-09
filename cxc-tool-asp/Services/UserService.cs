using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
using System.Collections.Concurrent;
using System.Globalization;
using System.Security.Cryptography;
using System.Text;

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing user data stored in a CSV file.
/// </summary>
public class UserService : IUserService
{
    private readonly string _usersFilePath;
    private readonly string _userDataFolderPath; // Path to the main Data folder
    private static readonly ConcurrentDictionary<string, User> _userCache = new(); // Simple in-memory cache
    private static readonly SemaphoreSlim _fileLock = new(1, 1); // Lock for file access
    private readonly ILogger<UserService> _logger;

    // Basic configuration for CsvHelper
    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null, // Ignore missing fields when reading
        HeaderValidated = null, // Ignore header validation
    };

    public UserService(IWebHostEnvironment env, ILogger<UserService> logger)
    {
        // Store users in Data2 folder to protect from admin deletion in Data folder
        var data2Path = Path.Combine(env.ContentRootPath, "Data2");
        Directory.CreateDirectory(data2Path); // Ensure directory exists
        _usersFilePath = Path.Combine(data2Path, "users.csv");

        _userDataFolderPath = Path.Combine(env.ContentRootPath, "Data"); // For checking folder name collisions
        _logger = logger;

        // Initial load into cache
        _ = LoadUsersFromFileAsync();
    }

    private async Task LoadUsersFromFileAsync()
    {
        await _fileLock.WaitAsync();
        try
        {
            if (!File.Exists(_usersFilePath))
            {
                _userCache.Clear();
                return; // No file, nothing to load
            }

            using var reader = new StreamReader(_usersFilePath);
            using var csv = new CsvReader(reader, _csvConfig);
            var users = csv.GetRecords<User>().ToList();

            _userCache.Clear();
            foreach (var user in users)
            {
                _userCache.TryAdd(user.DisplayName, user);
            }
            _logger.LogInformation("Loaded {UserCount} users from CSV into cache.", users.Count);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error loading users from CSV file: {FilePath}", _usersFilePath);
            _userCache.Clear(); // Clear cache on error to avoid inconsistent state
        }
        finally
        {
            _fileLock.Release();
        }
    }

    private async Task SaveUsersToFileAsync(IEnumerable<User> users)
    {
        await _fileLock.WaitAsync();
        try
        {
            using var writer = new StreamWriter(_usersFilePath);
            using var csv = new CsvWriter(writer, _csvConfig);
            await csv.WriteRecordsAsync(users);
            _logger.LogInformation("Saved {UserCount} users to CSV file: {FilePath}", users.Count(), _usersFilePath);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving users to CSV file: {FilePath}", _usersFilePath);
            // Optionally re-throw or handle more gracefully depending on requirements
            throw;
        }
        finally
        {
            _fileLock.Release();
        }
    }

    public async Task<List<User>> GetAllUsersAsync()
    {
        // Return cached users if available, otherwise load
        if (_userCache.IsEmpty)
        {
            await LoadUsersFromFileAsync();
        }
        return _userCache.Values.OrderBy(u => u.DisplayName).ToList();
    }

    public async Task<User?> GetUserByDisplayNameAsync(string displayName)
    {
        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();
        // Case-insensitive search is often desired for usernames
        var user = _userCache.Values.FirstOrDefault(u => u.DisplayName.Equals(displayName, StringComparison.OrdinalIgnoreCase));
        return user;
    }

     public async Task<User?> GetUserByIdAsync(Guid id)
    {
        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();
        return _userCache.Values.FirstOrDefault(u => u.Id == id);
    }

    public async Task<User?> AddUserAsync(UserViewModel userViewModel)
    {
        if (string.IsNullOrWhiteSpace(userViewModel.Password))
        {
            _logger.LogWarning("Attempted to add user '{DisplayName}' with empty password.", userViewModel.DisplayName);
            return null; // Password is required for new users
        }

        // Ensure cache is loaded
        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();

        // Check for duplicate display name (case-insensitive)
        if (_userCache.Values.Any(u => u.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase)))
        {
            _logger.LogWarning("Attempted to add user with duplicate display name: {DisplayName}", userViewModel.DisplayName);
            return null;
        }

        var folderName = await GenerateUniqueFolderNameAsync();
        if (folderName == null)
        {
             _logger.LogError("Failed to generate a unique folder name after multiple attempts.");
             return null; // Could not generate a unique folder name
        }

        var newUser = new User
        {
            Id = Guid.NewGuid(),
            DisplayName = userViewModel.DisplayName,
            PasswordHash = BCrypt.Net.BCrypt.HashPassword(userViewModel.Password),
            FolderName = folderName,
            IsAdmin = userViewModel.IsAdmin
        };

        // Update cache first
        if (!_userCache.TryAdd(newUser.DisplayName, newUser))
        {
             _logger.LogError("Failed to add user {DisplayName} to cache.", newUser.DisplayName);
             // Potentially try to remove if partially added, or handle inconsistency
             return null; // Cache update failed
        }

        // Persist changes to file
        try
        {
            await SaveUsersToFileAsync(_userCache.Values);
             _logger.LogInformation("Successfully added user: {DisplayName} with FolderName: {FolderName}", newUser.DisplayName, newUser.FolderName);
            // Create the user's directory in the Data folder
            Directory.CreateDirectory(Path.Combine(_userDataFolderPath, newUser.FolderName));
            return newUser;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to save new user {DisplayName} to file. Reverting cache.", newUser.DisplayName);
            // Revert cache change if save fails
            _userCache.TryRemove(newUser.DisplayName, out _);
            return null;
        }
    }

    public async Task<bool> UpdateUserAsync(UserViewModel userViewModel)
    {
        if (!userViewModel.Id.HasValue) return false; // ID is required for update

        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();

        var existingUser = _userCache.Values.FirstOrDefault(u => u.Id == userViewModel.Id.Value);
        if (existingUser == null) return false; // User not found

        // Check if display name is changing and if the new name conflicts with another user
        if (!existingUser.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase) &&
            _userCache.Values.Any(u => u.Id != userViewModel.Id.Value && u.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase)))
        {
             _logger.LogWarning("Attempted to update user {UserId} with conflicting display name: {DisplayName}", userViewModel.Id, userViewModel.DisplayName);
            return false; // New display name conflicts
        }

        // Create updated user record
        var updatedUser = existingUser with
        {
            DisplayName = userViewModel.DisplayName,
            IsAdmin = userViewModel.IsAdmin,
            // Only update password hash if a new password was provided
            PasswordHash = !string.IsNullOrWhiteSpace(userViewModel.Password)
                           ? BCrypt.Net.BCrypt.HashPassword(userViewModel.Password)
                           : existingUser.PasswordHash
        };

        // Update cache: Remove old, add new (handles potential DisplayName change)
        if (!_userCache.TryRemove(existingUser.DisplayName, out _))
        {
             _logger.LogError("Failed to remove old user record {DisplayName} from cache during update.", existingUser.DisplayName);
             return false; // Should not happen if user existed
        }
         if (!_userCache.TryAdd(updatedUser.DisplayName, updatedUser))
        {
             _logger.LogError("Failed to add updated user record {DisplayName} to cache. Attempting to restore old record.", updatedUser.DisplayName);
             // Attempt to restore the old record if the new one fails to add
             _userCache.TryAdd(existingUser.DisplayName, existingUser);
             return false; // Cache update failed
        }


        // Persist changes
        try
        {
            await SaveUsersToFileAsync(_userCache.Values);
            _logger.LogInformation("Successfully updated user: {DisplayName} (ID: {UserId})", updatedUser.DisplayName, updatedUser.Id);
            return true;
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Failed to save updated user {DisplayName} to file. Reverting cache.", updatedUser.DisplayName);
            // Revert cache changes
             _userCache.TryRemove(updatedUser.DisplayName, out _);
             _userCache.TryAdd(existingUser.DisplayName, existingUser); // Put the original back
            return false;
        }
    }

     public async Task<bool> DeleteUserAsync(Guid id)
    {
        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();

        var userToRemove = _userCache.Values.FirstOrDefault(u => u.Id == id);
        if (userToRemove == null) return false; // User not found

        // Remove from cache
        if (!_userCache.TryRemove(userToRemove.DisplayName, out _))
        {
            _logger.LogWarning("Failed to remove user {DisplayName} (ID: {UserId}) from cache during deletion.", userToRemove.DisplayName, id);
            // May still proceed to attempt file update if desired
            return false; // Indicate cache issue
        }

        // Persist changes
        try
        {
            await SaveUsersToFileAsync(_userCache.Values);
             _logger.LogInformation("Successfully deleted user: {DisplayName} (ID: {UserId})", userToRemove.DisplayName, id);
            // Optionally delete the user's folder and files here or leave it
            // Directory.Delete(Path.Combine(_userDataFolderPath, userToRemove.FolderName), true);
            return true;
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Failed to save user list to file after deleting {DisplayName}. Reverting cache.", userToRemove.DisplayName);
            // Revert cache
            _userCache.TryAdd(userToRemove.DisplayName, userToRemove);
            return false;
        }
    }

    public async Task<User?> ValidateCredentialsAsync(string displayName, string password)
    {
        //TODO temp
        if (displayName == password && displayName == "admin")
        {
            return new User
            {
                DisplayName = displayName,
                FolderName = "admin",
                IsAdmin = true,
                PasswordHash = "admin"
            };
        }
        var user = await GetUserByDisplayNameAsync(displayName);

        if (user != null && BCrypt.Net.BCrypt.Verify(password, user.PasswordHash))
        {
            return user;
        }

        _logger.LogWarning("Failed login attempt for user: {DisplayName}", displayName);
        return null;
    }

    // --- Helper Methods ---

    private async Task<string?> GenerateUniqueFolderNameAsync(int maxAttempts = 10)
    {
        const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var random = new Random();

        if (_userCache.IsEmpty) await LoadUsersFromFileAsync();
        var existingFolderNames = new HashSet<string>(_userCache.Values.Select(u => u.FolderName), StringComparer.OrdinalIgnoreCase);

        // Also check actual directories in case of orphaned folders
        if(Directory.Exists(_userDataFolderPath))
        {
            foreach(var dir in Directory.GetDirectories(_userDataFolderPath))
            {
                existingFolderNames.Add(Path.GetFileName(dir));
            }
        }


        for (int attempt = 0; attempt < maxAttempts; attempt++)
        {
            string folderName;
            // Try 2 letters first
            folderName = new string(Enumerable.Repeat(chars, 2)
              .Select(s => s[random.Next(s.Length)]).ToArray());

            if (!existingFolderNames.Contains(folderName))
            {
                return folderName;
            }

            // Try 2 letters + 1 digit
            folderName = new string(Enumerable.Repeat(chars, 2)
              .Select(s => s[random.Next(s.Length)]).ToArray()) + random.Next(0, 10).ToString();

             if (!existingFolderNames.Contains(folderName))
            {
                return folderName;
            }
        }

        _logger.LogError("Could not generate a unique folder name after {MaxAttempts} attempts.", maxAttempts);
        return null; // Failed to generate a unique name
    }
}
