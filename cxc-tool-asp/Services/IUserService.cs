using cxc_tool_asp.Models;

namespace cxc_tool_asp.Services;

/// <summary>
/// Interface for managing user data and authentication logic.
/// </summary>
public interface IUserService
{
    /// <summary>
    /// Retrieves all users from the data store.
    /// </summary>
    /// <returns>A list of all users.</returns>
    Task<List<User>> GetAllUsersAsync();

    /// <summary>
    /// Retrieves a user by their display name.
    /// </summary>
    /// <param name="displayName">The display name of the user to find.</param>
    /// <returns>The user object if found; otherwise, null.</returns>
    Task<User?> GetUserByDisplayNameAsync(string displayName);

    /// <summary>
    /// Retrieves a user by their unique ID.
    /// </summary>
    /// <param name="id">The ID of the user to find.</param>
    /// <returns>The user object if found; otherwise, null.</returns>
    Task<User?> GetUserByIdAsync(Guid id);

    /// <summary>
    /// Adds a new user to the data store.
    /// Handles password hashing and folder name generation.
    /// </summary>
    /// <param name="userViewModel">The view model containing the new user's details.</param>
    /// <returns>The newly created User object, or null if creation failed (e.g., display name exists).</returns>
    Task<User?> AddUserAsync(UserViewModel userViewModel);

    /// <summary>
    /// Updates an existing user's details.
    /// Handles optional password update and hashing.
    /// </summary>
    /// <param name="userViewModel">The view model containing the updated user details.</param>
    /// <returns>True if the update was successful; otherwise, false.</returns>
    Task<bool> UpdateUserAsync(UserViewModel userViewModel);

    /// <summary>
    /// Deletes a user from the data store.
    /// </summary>
    /// <param name="id">The ID of the user to delete.</param>
    /// <returns>True if the deletion was successful; otherwise, false.</returns>
    Task<bool> DeleteUserAsync(Guid id);

    /// <summary>
    /// Validates a user's credentials.
    /// </summary>
    /// <param name="displayName">The user's display name.</param>
    /// <param name="password">The password provided by the user.</param>
    /// <returns>The User object if credentials are valid; otherwise, null.</returns>
    Task<User?> ValidateCredentialsAsync(string displayName, string password);
}
