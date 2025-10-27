// Security check function
function validateUserAccess($user_id) {
    // You can add additional security checks here
    if (empty($user_id) || !is_numeric($user_id)) {
        throw new Exception('Invalid user access');
    }
    return true;
}