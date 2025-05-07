<?php
// admin.php
session_start();
$page_title = "لوحة الإدارة - متجر مشهور";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

// Get active section based on URL parameter
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Include notification functions file
include_once 'notification_functions.php';

// Include referral functions
include_once 'referral_functions.php';

// Process user management - adding a new user
if (isset($_POST['add_user'])) {
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
    $new_full_name = filter_input(INPUT_POST, 'new_full_name', FILTER_SANITIZE_STRING);
    $new_email = filter_input(INPUT_POST, 'new_email', FILTER_SANITIZE_EMAIL);
    $new_phone = filter_input(INPUT_POST, 'new_phone', FILTER_SANITIZE_STRING);
    $new_country = filter_input(INPUT_POST, 'new_country', FILTER_SANITIZE_STRING);
    $new_balance = filter_input(INPUT_POST, 'new_balance', FILTER_VALIDATE_FLOAT) ?: 0;
    $new_role = filter_input(INPUT_POST, 'new_role', FILTER_SANITIZE_STRING);
    $new_password = $_POST['new_user_password'] ?? '';
    
    // Validate required fields
    $errors = [];
    
    if (empty($new_username)) {
        $errors[] = "اسم المستخدم مطلوب";
    }
    
    if (empty($new_email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }
    
    if (empty($new_full_name)) {
        $errors[] = "الاسم الكامل مطلوب";
    }
    
    if (empty($new_password)) {
        $errors[] = "كلمة المرور مطلوبة";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "يجب أن تكون كلمة المرور 6 أحرف على الأقل";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $new_username, $new_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['username'] === $new_username) {
                $errors[] = "اسم المستخدم مستخدم بالفعل";
            }
            if ($existing_user['email'] === $new_email) {
                $errors[] = "البريد الإلكتروني مستخدم بالفعل";
            }
        }
    }
    
    // Insert new user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Insert user
        $insert_query = "INSERT INTO users (username, email, password, full_name, phone, country, balance, role, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssssids", $new_username, $new_email, $hashed_password, $new_full_name, $new_phone, 
                                     $new_country, $new_balance, $new_role);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            
            // Generate and set referral code
            $referral_code = generateReferralCode($new_user_id);
            $update_code = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
            $update_code->bind_param("si", $referral_code, $new_user_id);
            $update_code->execute();
            
            // Log the action
            $admin_id = $_SESSION['user_id'];
            $log_action = "إضافة مستخدم جديد: " . $new_username;
            logAdminAction($conn, $admin_id, 'add_user', $log_action);
            
            $success_message = "تم إضافة المستخدم بنجاح.";
        } else {
            $error_message = "حدث خطأ أثناء إضافة المستخدم: " . $conn->error;
        }
    } else {
        $error_message = "يرجى تصحيح الأخطاء التالية:<br>" . implode("<br>", $errors);
    }
}

// Process editing a user
if (isset($_POST['edit_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
    $balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $referral_percentage = filter_input(INPUT_POST, 'referral_percentage', FILTER_VALIDATE_FLOAT);
    $new_password = $_POST['new_password'] ?? '';
    
    // Get original user data for comparison
    $original_user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($original_user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $original_user = $stmt->get_result()->fetch_assoc();
    
    // Check if email is being changed and if it already exists
    $errors = [];
    if ($email !== $original_user['email']) {
        $check_email_query = "SELECT * FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "البريد الإلكتروني مستخدم بالفعل";
        }
    }
    
    // Check if username is being changed and if it already exists
    if ($username !== $original_user['username']) {
        $check_username_query = "SELECT * FROM users WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($check_username_query);
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "اسم المستخدم مستخدم بالفعل";
        }
    }
    
    // Update user if no errors
    if (empty($errors)) {
        // Start with basic user data update
        $update_query = "UPDATE users SET 
                         username = ?, 
                         full_name = ?, 
                         email = ?, 
                         phone = ?, 
                         country = ?, 
                         role = ?, 
                         balance = ?";
        
        // Track parameter types and values
        $types = "ssssssd";
        $params = [$username, $full_name, $email, $phone, $country, $role, $balance];
        
        // Add referral percentage if provided
        if ($referral_percentage !== false) {
            $update_query .= ", referral_percentage = ?";
            $types .= "d";
            $params[] = $referral_percentage;
        }
        
        // Add new password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query .= ", password = ?";
            $types .= "s";
            $params[] = $hashed_password;
        }
        
        // Complete the query
        $update_query .= " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;
        
        // Execute update
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Track balance change
            if ($balance != $original_user['balance']) {
                $balance_diff = $balance - $original_user['balance'];
                
                // Add transaction record if balance changed
                if ($balance_diff != 0) {
                    $transaction_type = $balance_diff > 0 ? 'deposit' : 'withdraw';
                    $transaction_amount = abs($balance_diff);
                    $transaction_description = $balance_diff > 0 
                                              ? "تعديل الرصيد بواسطة الإدارة (إضافة)" 
                                              : "تعديل الرصيد بواسطة الإدارة (خصم)";
                    
                    $transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) 
                                        VALUES (?, ?, ?, 'completed', ?)";
                    $stmt = $conn->prepare($transaction_query);
                    $stmt->bind_param("idss", $user_id, $transaction_amount, $transaction_type, $transaction_description);
                    $stmt->execute();
                    
                    // Send notification about balance change
                    $notification_title = $balance_diff > 0 ? "تمت إضافة رصيد لحسابك" : "تم خصم رصيد من حسابك";
                    $notification_message = $balance_diff > 0 
                                           ? "تمت إضافة $" . number_format($transaction_amount, 2) . " إلى رصيدك بواسطة الإدارة."
                                           : "تم خصم $" . number_format($transaction_amount, 2) . " من رصيدك بواسطة الإدارة.";
                    
                    // Use notification utility function
                    send_notification($user_id, $notification_title, $notification_message, 'payment', 'fas fa-wallet');
                }
            }
            
            // Log the action
            $admin_id = $_SESSION['user_id'];
            $log_action = "تعديل المستخدم: " . $username;
            logAdminAction($conn, $admin_id, 'edit_user', $log_action);
            
            $success_message = "تم تحديث بيانات المستخدم بنجاح.";
        } else {
            $error_message = "حدث خطأ أثناء تحديث بيانات المستخدم: " . $conn->error;
        }
    } else {
        $error_message = "يرجى تصحيح الأخطاء التالية:<br>" . implode("<br>", $errors);
    }
}

// Process user suspension/activation
if (isset($_POST['toggle_user_status'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
    
    $update_query = "UPDATE users SET is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $new_status, $user_id);
    
    if ($stmt->execute()) {
        // Get user info for notification
        $user_query = "SELECT username FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        // Log the action
        $admin_id = $_SESSION['user_id'];
        $action_type = $new_status == 1 ? 'activate_user' : 'suspend_user';
        $log_action = $new_status == 1 
                     ? "تفعيل المستخدم: " . $user['username'] 
                     : "تعليق المستخدم: " . $user['username'];
        logAdminAction($conn, $admin_id, $action_type, $log_action);
        
        // Send notification to user
        $notification_title = $new_status == 1 ? "تم تفعيل حسابك" : "تم تعليق حسابك";
        $notification_message = $new_status == 1 
                              ? "تم تفعيل حسابك بنجاح. يمكنك الآن استخدام جميع خدمات الموقع." 
                              : "تم تعليق حسابك. يرجى التواصل مع الدعم الفني لمزيد من المعلومات.";
        $icon = $new_status == 1 ? "fas fa-check-circle" : "fas fa-ban";
        
        // Use notification function
        send_notification($user_id, $notification_title, $notification_message, 'system', $icon);
        
        $success_message = $new_status == 1 
                         ? "تم تفعيل المستخدم بنجاح." 
                         : "تم تعليق المستخدم بنجاح.";
    } else {
        $error_message = "حدث خطأ أثناء تحديث حالة المستخدم: " . $conn->error;
    }
}

if ($section === 'notifications') {
    include_once 'admin_notification_handler.php';
}

// Process order status update
if (isset($_POST['update_order_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $remains = isset($_POST['remains']) ? filter_input(INPUT_POST, 'remains', FILTER_SANITIZE_NUMBER_INT) : 0;
    $start_count = isset($_POST['start_count']) ? filter_input(INPUT_POST, 'start_count', FILTER_SANITIZE_NUMBER_INT) : null;
    
    // Get current order
    $order_query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if ($order) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // If starting processing, update the start_count
            if ($new_status === 'processing' && $start_count !== null) {
                $update_query = "UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("siii", $new_status, $start_count, $remains, $order_id);
            } else {
                // Update order status
                $update_query = "UPDATE orders SET status = ?, remains = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sii", $new_status, $remains, $order_id);
            }
            $stmt->execute();
            
            // If status is completed, update spent and in_use in a single query
            if ($new_status === 'completed' && $order['status'] !== 'completed') {
                // Update user's spent amount and decrease in_use
                $update_spent_query = "UPDATE users SET spent = spent + ?, in_use = in_use - ? WHERE id = ?";
                $stmt = $conn->prepare($update_spent_query);
                $stmt->bind_param("ddi", $order['amount'], $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Process pending referral reward
                completePendingReferralReward($order_id);
            }
            // For cancelled orders
            else if ($new_status === 'cancelled' && $order['status'] !== 'cancelled') {
                // Release in_use balance
                $update_balance_query = "UPDATE users SET in_use = in_use - ? WHERE id = ?";
                $stmt = $conn->prepare($update_balance_query);
                $stmt->bind_param("di", $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Refund amount to user balance
                $refund_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = $conn->prepare($refund_query);
                $stmt->bind_param("di", $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Create refund transaction
                $description = "استرداد المبلغ لإلغاء الطلب #" . $order_id;
                $insert_transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) VALUES (?, ?, 'refund', 'completed', ?)";
                $stmt = $conn->prepare($insert_transaction_query);
                $stmt->bind_param("ids", $order['user_id'], $order['amount'], $description);
                $stmt->execute();
            }
            // For failed orders
            else if ($new_status === 'failed' && $order['status'] !== 'failed') {
                // Similar to cancelled, release in_use balance
                $update_balance_query = "UPDATE users SET in_use = in_use - ? WHERE id = ?";
                $stmt = $conn->prepare($update_balance_query);
                $stmt->bind_param("di", $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Refund amount to user balance
                $refund_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = $conn->prepare($refund_query);
                $stmt->bind_param("di", $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Create refund transaction
                $description = "استرداد المبلغ لفشل الطلب #" . $order_id;
                $insert_transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) VALUES (?, ?, 'refund', 'completed', ?)";
                $stmt = $conn->prepare($insert_transaction_query);
                $stmt->bind_param("ids", $order['user_id'], $order['amount'], $description);
                $stmt->execute();
            }
            
            // For partial delivery, adjust remaining balance
            if ($new_status === 'partial' && $order['status'] !== 'partial') {
                $delivered_percentage = ($order['quantity'] - $remains) / $order['quantity'];
                $used_amount = $order['amount'] * $delivered_percentage;
                $refund_amount = $order['amount'] - $used_amount;
                
                // Release in_use balance
                $update_inuse_query = "UPDATE users SET in_use = in_use - ? WHERE id = ?";
                $stmt = $conn->prepare($update_inuse_query);
                $stmt->bind_param("di", $order['amount'], $order['user_id']);
                $stmt->execute();
                
                // Add the used amount to spent
                $update_spent_query = "UPDATE users SET spent = spent + ? WHERE id = ?";
                $stmt = $conn->prepare($update_spent_query);
                $stmt->bind_param("di", $used_amount, $order['user_id']);
                $stmt->execute();
                
                // Refund the unused amount
                $refund_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = $conn->prepare($refund_query);
                $stmt->bind_param("di", $refund_amount, $order['user_id']);
                $stmt->execute();
                
                // Create partial refund transaction
                $description = "استرداد جزئي للطلب #" . $order_id;
                $insert_transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) VALUES (?, ?, 'refund', 'completed', ?)";
                $stmt = $conn->prepare($insert_transaction_query);
                $stmt->bind_param("ids", $order['user_id'], $refund_amount, $description);
                $stmt->execute();
            }
            
            // Record status change in order history table
            if ($new_status !== $order['status']) {
                $insert_history_query = "INSERT INTO order_history 
                    (order_id, old_status, new_status, changed_by, changed_at, notes) 
                    VALUES (?, ?, ?, ?, NOW(), ?)";
                $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'system';
                $notes = "تم تحديث الحالة بواسطة المدير";
                
                $stmt = $conn->prepare($insert_history_query);
                $stmt->bind_param("issss", $order_id, $order['status'], $new_status, $admin_id, $notes);
                $stmt->execute();
                
                // Send notification to user about status change
                send_order_notification($order['user_id'], $order_id, $new_status);
            }
            
            $conn->commit();
            $success_message = "تم تحديث حالة الطلب بنجاح.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "حدث خطأ أثناء تحديث حالة الطلب: " . $e->getMessage();
        }
    } else {
        $error_message = "الطلب غير موجود.";
    }
}

// Process payment approval - with fix for 'updated_at' column error
if (isset($_POST['approve_payment'])) {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Get transaction details
    $transaction_query = "SELECT * FROM transactions WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($transaction_query);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    if ($transaction) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if updated_at column exists
            $column_check = $conn->query("SHOW COLUMNS FROM transactions LIKE 'updated_at'");
            
            if ($column_check->num_rows > 0) {
                // Use updated_at if it exists
                $update_query = "UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = ?";
            } else {
                // Don't use updated_at if it doesn't exist
                $update_query = "UPDATE transactions SET status = 'completed' WHERE id = ?";
            }
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            
            // Add amount to user's balance
            $update_balance_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($update_balance_query);
            $stmt->bind_param("di", $transaction['amount'], $transaction['user_id']);
            $stmt->execute();
            
            // Send notification to user about payment approval
            send_payment_notification($transaction['user_id'], $transaction_id, 'completed', $transaction['amount']);
            
            $conn->commit();
            $success_message = "تم اعتماد عملية الدفع بنجاح.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "حدث خطأ أثناء اعتماد عملية الدفع: " . $e->getMessage();
        }
    } else {
        $error_message = "عملية الدفع غير موجودة أو تم اعتمادها بالفعل.";
    }
}

// Process payment rejection - with fix for 'updated_at' column error
if (isset($_POST['reject_payment'])) {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_NUMBER_INT);
    $rejection_reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
    
    // Get transaction details
    $transaction_query = "SELECT * FROM transactions WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($transaction_query);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    if ($transaction) {
        // Check if updated_at column exists
        $column_check = $conn->query("SHOW COLUMNS FROM transactions LIKE 'updated_at'");
        
        if ($column_check->num_rows > 0) {
            // Use updated_at if it exists
            $update_query = "UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?";
        } else {
            // Don't use updated_at if it doesn't exist
            $update_query = "UPDATE transactions SET status = 'failed' WHERE id = ?";
        }
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $transaction_id);
        
        if ($stmt->execute()) {
            // Send notification to user about payment rejection
            send_payment_notification($transaction['user_id'], $transaction_id, 'failed', $transaction['amount'], $rejection_reason);
            
            $success_message = "تم رفض عملية الدفع.";
        } else {
            $error_message = "حدث خطأ أثناء رفض عملية الدفع.";
        }
    } else {
        $error_message = "عملية الدفع غير موجودة أو تم اعتمادها بالفعل.";
    }
}

// Process add funds to user
if (isset($_POST['add_funds'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    // Check if we have either username or user_id
    if (($username || $user_id) && $amount > 0) {
        // Get user details
        if ($user_id) {
            $user_query = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("i", $user_id);
        } else {
            $user_query = "SELECT * FROM users WHERE username = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("s", $username);
        }
        
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Add amount to user's balance
                $update_balance_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = $conn->prepare($update_balance_query);
                $stmt->bind_param("di", $amount, $user['id']);
                $stmt->execute();
                
                // Create transaction record
                $insert_transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) VALUES (?, ?, 'deposit', 'completed', ?)";
                $stmt = $conn->prepare($insert_transaction_query);
                
                // Create description if not provided
                if (empty($description)) {
                    $description = "إضافة رصيد بواسطة الإدارة - " . $payment_method;
                }
                
                $stmt->bind_param("ids", $user['id'], $amount, $description);
                $stmt->execute();
                $transaction_id = $conn->insert_id;
                
                // Send notification about manual fund addition
                send_balance_added_notification($user['id'], $amount, $payment_method);
                
                $conn->commit();
                $success_message = "تم إضافة الرصيد بنجاح للمستخدم " . $user['username'];
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "حدث خطأ أثناء إضافة الرصيد: " . $e->getMessage();
            }
        } else {
            $error_message = "المستخدم غير موجود.";
        }
    } else {
        $error_message = "الرجاء التأكد من صحة البيانات المدخلة.";
    }
}

// Process adding gift to user
if (isset($_POST['add_gift'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'gift_amount', FILTER_VALIDATE_FLOAT);
    $reason = filter_input(INPUT_POST, 'gift_reason', FILTER_SANITIZE_STRING);
    $custom_reason = filter_input(INPUT_POST, 'custom_reason', FILTER_SANITIZE_STRING);
    
    // Use custom reason if selected
    if ($reason === 'أخرى' && !empty($custom_reason)) {
        $reason = $custom_reason;
    }
    
    if ($username && $amount > 0) {
        // Get user details
        $user_query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Add amount to user's balance
                $update_balance_query = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt = $conn->prepare($update_balance_query);
                $stmt->bind_param("di", $amount, $user['id']);
                $stmt->execute();
                
                // Create transaction record
                $description = "هدية من الإدارة: " . $reason;
                $insert_transaction_query = "INSERT INTO transactions (user_id, amount, type, status, description) VALUES (?, ?, 'deposit', 'completed', ?)";
                $stmt = $conn->prepare($insert_transaction_query);
                $stmt->bind_param("ids", $user['id'], $amount, $description);
                $stmt->execute();
                
                // Send gift notification to user
                send_gift_notification($user['id'], $amount, $reason);
                
                $conn->commit();
                $success_message = "تم إضافة الهدية بنجاح للمستخدم " . $username;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "حدث خطأ أثناء إضافة الهدية: " . $e->getMessage();
            }
        } else {
            $error_message = "المستخدم غير موجود.";
        }
    } else {
        $error_message = "الرجاء التأكد من صحة البيانات المدخلة.";
    }
}

// Helper function to log admin actions
function logAdminAction($conn, $admin_id, $action_type, $description) {
    // Check if admin_logs table exists
    $check_table_query = "SHOW TABLES LIKE 'admin_logs'";
    $table_exists = $conn->query($check_table_query)->num_rows > 0;
    
    if (!$table_exists) {
        // Create admin_logs table
        $create_table_query = "CREATE TABLE admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_table_query);
    }
    
    // Get client IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Insert log
    $insert_log_query = "INSERT INTO admin_logs (admin_id, action_type, description, ip_address) 
                        VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_log_query);
    $stmt->bind_param("isss", $admin_id, $action_type, $description, $ip_address);
    $stmt->execute();
}

// Get stats for dashboard
$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$pending_orders_query = "SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'";
$processing_orders_query = "SELECT COUNT(*) as processing FROM orders WHERE status = 'processing'";
$completed_orders_query = "SELECT COUNT(*) as completed FROM orders WHERE status = 'completed'";
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_revenue_query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'";
$pending_payments_query = "SELECT COUNT(*) as pending FROM transactions WHERE type = 'deposit' AND status = 'pending'";

$total_orders = $conn->query($total_orders_query)->fetch_assoc()['total'];
$pending_orders = $conn->query($pending_orders_query)->fetch_assoc()['pending'];
$processing_orders = $conn->query($processing_orders_query)->fetch_assoc()['processing'];
$completed_orders = $conn->query($completed_orders_query)->fetch_assoc()['completed'];
$total_users = $conn->query($total_users_query)->fetch_assoc()['total'];
$total_revenue = $conn->query($total_revenue_query)->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query($pending_payments_query)->fetch_assoc()['pending'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/png" href="/images/logo.png" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #F44336;
            --background-color: #f8f9fa;
            --text-color: #333;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding-bottom: 20px;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: #212529;
            color: #fff;
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        }
        
        .admin-content {
            flex: 1;
            margin-right: var(--sidebar-width);
            padding: 20px;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }
        
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background-color: #343a40;
            border-bottom: 1px solid #495057;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .admin-logo img {
            width: 30px;
            height: 30px;
            margin-left: 10px;
        }
        
        .admin-menu {
            padding: 15px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #adb5bd;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: #343a40;
            color: #fff;
            border-right-color: var(--primary-color);
        }
        
        .menu-item i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-footer {
            padding: 15px 20px;
            font-size: 0.8rem;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #495057;
        }
        
        /* Dashboard Stats */
        .stats-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .stats-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .stats-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-card-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Custom styles for the admin pages */
        /* Icon Boxes */
        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.1);
        }

        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-primary-light {
            background-color: rgba(0, 123, 255, 0.1);
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
        }

        /* Actions Column Styling */
        .actions-column {
            min-width: 160px;
        }

        .actions-column .btn-group {
            display: flex;
            flex-wrap: wrap;
        }

        .actions-column .btn-group .btn {
            margin-right: 2px;
            margin-bottom: 2px;
        }

        /* Tab Badge Positioning */
        .nav-link .position-absolute {
            top: -8px !important;
            right: -8px !important;
            min-width: 20px; /* Ensure minimum width for the badge */
            height: 20px; /* Fixed height to match width */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px; /* Add horizontal padding */
            border-radius: 50%; /* Keep it circular */
        }

        /* User search styling */
        .position-relative {
            position: relative !important;
        }

        #searchResults, #orderSearchResults {
            position: absolute;
            width: 100%;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            z-index: 1050;
            max-height: 250px;
            overflow-y: auto;
        }

        .list-group-item.user-result, .list-group-item.order-result {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-left: none;
            border-right: none;
            border-radius: 0;
            transition: background-color 0.15s ease-in-out;
        }

        .list-group-item.user-result:first-child, .list-group-item.order-result:first-child {
            border-top: none;
        }

        .list-group-item.user-result:last-child, .list-group-item.order-result:last-child {
            border-bottom: none;
        }

        .list-group-item.user-result:hover, .list-group-item.order-result:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        /* Selected user info */
        #selected_user_info {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            animation: fadeIn 0.3s;
        }

        /* Highlight selected row */
        .highlight-row {
            background-color: rgba(0, 123, 255, 0.2) !important;
            transition: background-color 1s ease;
        }

        /* Better looking tables */
        .datatable thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .datatable tbody tr:hover {
            background-color: rgba(33, 150, 243, 0.05);
        }

        /* Badge styles */
        .badge {
            padding: 0.4em 0.65em;
            font-weight: 500;
        }

        /* Fix right-to-left display for DataTables */
        .dataTables_wrapper {
            direction: rtl;
        }

        .dataTables_filter, .dataTables_length {
            margin-bottom: 1rem;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive styling */
        @media (max-width: 767.98px) {
            .actions-column {
                min-width: auto;
            }
            
            .actions-column .btn-group {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .actions-column .btn {
                margin-bottom: 2px;
            }
        }
        
        /* Data Tables Customization */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        
        /* User search styling */
        .position-relative {
            position: relative !important;
        }

        #searchResults {
            position: absolute;
            width: 100%;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
        }

        .list-group-item.user-result {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-left: none;
            border-right: none;
            border-radius: 0;
        }

        .list-group-item.user-result:first-child {
            border-top: none;
        }

        .list-group-item.user-result:last-child {
            border-bottom: none;
        }

        .list-group-item.user-result:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        /* Selected user info */
        #selected_user_info {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Specific fixes for users section */
        .users-section table {
            width: 100% !important;
            table-layout: fixed !important;
            display: table !important;
            direction: rtl !important;
        }
        
        .users-section table tr {
            display: table-row !important;
            width: 100% !important;
        }
        
        .users-section table th,
        .users-section table td {
            display: table-cell !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .users-section .dataTables_wrapper {
            width: 100% !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 60px;
                overflow: visible;
            }
            
            .admin-content {
                margin-right: 60px;
            }
            
            .menu-text {
                display: none;
            }
            
            .admin-header {
                justify-content: center;
                padding: 10px;
            }
            
            .admin-logo span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 12px;
            }
            
            .menu-item i {
                margin: 0;
            }
            
            .admin-footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-header">
                <a href="admin.php" class="admin-logo">
                    <img src="/images/logo.png" alt="Logo">
                    <span>لوحة الإدارة</span>
                </a>
            </div>
            
            <div class="admin-menu">
                <a href="admin.php?section=dashboard" class="menu-item <?php echo $section === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">لوحة التحكم</span>
                </a>
                
                <a href="admin.php?section=orders" class="menu-item <?php echo $section === 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="menu-text">إدارة الطلبات</span>
                    <?php if ($pending_orders > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_orders; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="admin.php?section=payments" class="menu-item <?php echo $section === 'payments' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span class="menu-text">إدارة المدفوعات</span>
                    <?php if ($pending_payments > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_payments; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="admin.php?section=users" class="menu-item <?php echo $section === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">إدارة المستخدمين</span>
                </a>
                
                <a href="admin.php?section=services" class="menu-item <?php echo $section === 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="menu-text">إدارة الخدمات</span>
                </a>
                
                <a href="admin.php?section=support" class="menu-item <?php echo $section === 'support' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span class="menu-text">الدعم الفني</span>
                </a>
                
                <a href="admin.php?section=notifications" class="menu-item <?php echo $section === 'notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span class="menu-text">الإشعارات</span>
                </a>
                
                <a href="admin.php?section=referrals" class="menu-item <?php echo $section === 'referrals' ? 'active' : ''; ?>">
                    <i class="fas fa-share-alt"></i>
                    <span class="menu-text">نظام الإحالة</span>
                </a>
                
                <a href="admin.php?section=gifts" class="menu-item <?php echo $section === 'gifts' ? 'active' : ''; ?>">
                    <i class="fas fa-gift"></i>
                    <span class="menu-text">الهدايا والمكافآت</span>
                </a>
                
                <a href="admin.php?section=settings" class="menu-item <?php echo $section === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">إعدادات النظام</span>
                </a>
                
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">تسجيل الخروج</span>
                </a>
            </div>
            
            <div class="admin-footer">
                &copy; <?php echo date('Y'); ?> متجر مشهور
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php 
            // Display different sections based on selected section
            switch ($section) {
                case 'dashboard':
                    include 'admin/dashboard.php';
                    break;
                case 'orders':
                    include 'admin/orders.php';
                    break;
                case 'payments':
                    include 'admin/payments.php';
                    break;
                case 'users':
                    echo '<div class="section-users-container w-100">';
                    include 'admin/users.php';
                    echo '</div>';
                    break;
                case 'services':
                    include 'admin/services.php';
                    break;
                case 'support':
                    include 'admin/support.php';
                    break;
                case 'notifications':
                    include 'admin/notifications.php';
                    break;
                case 'referrals':
                    include 'admin/referral_settings.php';
                    break;
                case 'gifts':
                    include 'admin/gifts.php';
                    break;
                case 'settings':
                    include 'admin/settings.php';
                    break;
                default:
                    include 'admin/dashboard.php';
            }
            ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced user search functionality with real-time results
            const userSearchInput = $('#userSearch');
            const searchResults = $('#searchResults');
            const selectedUserInfo = $('#selected_user_info');
            const userIdInput = $('#user_id');
            
            if (userSearchInput.length) {
                userSearchInput.on('input', function() {
                    const username = $(this).val();
                    
                    // Only search if at least 2 characters
                    if (username.length >= 2) {
                        $.ajax({
                            url: 'admin/search_user.php',
                            method: 'POST',
                            data: { 
                                username: username,
                                user_type: 'all' 
                            },
                            dataType: 'json',
                            beforeSend: function() {
                                // Show loading indicator
                                searchResults.html('<div class="p-2 text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>');
                                searchResults.show();
                            },
                            success: function(response) {
                                let results = '';
                                if (response.length > 0) {
                                    // Create user list
                                    results += '<div class="list-group">';
                                    response.forEach(function(user) {
                                        results += `<a href="#" class="list-group-item list-group-item-action user-result d-flex justify-content-between align-items-center" 
                                                    data-id="${user.id}" 
                                                    data-username="${user.username}"
                                                    data-email="${user.email}">
                                                    <div>
                                                        <strong>${user.username}</strong>
                                                        <small class="d-block text-muted">${user.email}</small>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill">اختيار</span>
                                                    </a>`;
                                    });
                                    results += '</div>';
                                } else {
                                    results = '<div class="p-3 text-center text-muted">لا توجد نتائج</div>';
                                }
                                searchResults.html(results);
                            },
                            error: function() {
                                searchResults.html('<div class="p-3 text-center text-danger">حدث خطأ أثناء البحث</div>');
                            }
                        });
                    } else {
                        searchResults.hide();
                    }
                });
                
                // Handle clicking outside the search results to hide them
                $(document).on('click', function(e) {
                    if (!userSearchInput.is(e.target) && !searchResults.is(e.target) && searchResults.has(e.target).length === 0) {
                        searchResults.hide();
                    }
                });
                
                // Handle selecting a user from search results
                $(document).on('click', '.user-result', function(e) {
                    e.preventDefault();
                    const userId = $(this).data('id');
                    const username = $(this).data('username');
                    const email = $(this).data('email');
                    
                    // Set the selected username in the input
                    userSearchInput.val(username);
                    
                    // Store user ID in the hidden field
                    userIdInput.val(userId);
                    
                    // Add user info below the search field
                    if (selectedUserInfo.length === 0) {
                        userSearchInput.parent().append('<div id="selected_user_info" class="mt-2 p-2 bg-light rounded"></div>');
                        selectedUserInfo = $('#selected_user_info');
                    }
                    
                    selectedUserInfo.html(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-info">المستخدم المحدد</span>
                                <span class="ms-2">${username} (${email})</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary clear-user">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).show();
                    
                    // Hide search results
                    searchResults.hide();
                });
                
                // Clear selected user
                $(document).on('click', '.clear-user', function() {
                    userSearchInput.val('');
                    userIdInput.val('');
                    selectedUserInfo.hide();
                });
            }
            
            // Enhanced order search functionality
            const orderSearch = $('#orderSearch');
            const orderSearchResults = $('#searchResults');
            
            if (orderSearch.length) {
                orderSearch.on('input', function() {
                    const searchTerm = $(this).val();
                    
                    // Only search if at least 2 characters
                    if (searchTerm.length >= 2) {
                        // Show loading indicator
                        orderSearchResults.html('<div class="p-2 text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>');
                        orderSearchResults.show();
                        
                        // Perform search on all visible rows in active tab
                        const activeTab = $('.tab-pane.active');
                        const rows = activeTab.find('tbody tr');
                        let matches = [];
                        
                        rows.each(function() {
                            const id = $(this).find('td:first-child').text().toLowerCase();
                            const username = $(this).find('td:nth-child(2)').text().toLowerCase();
                            const service = $(this).find('td:nth-child(3)').text().toLowerCase();
                            
                            if (id.includes(searchTerm.toLowerCase()) || 
                                username.includes(searchTerm.toLowerCase()) || 
                                service.includes(searchTerm.toLowerCase())) {
                                matches.push({
                                    id: $(this).find('td:first-child').text(),
                                    username: $(this).find('td:nth-child(2)').text().trim(),
                                    service: $(this).find('td:nth-child(3)').text().trim()
                                });
                            }
                        });
                        
                        // Display results
                        if (matches.length > 0) {
                            let resultsHTML = '<div class="list-group">';
                            matches.forEach(function(match) {
                                resultsHTML += `<a href="#order_${match.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center order-result" 
                                            data-id="${match.id}">
                                    <div>
                                        <strong>#${match.id}</strong> - ${match.username}
                                        <small class="d-block text-muted">${match.service}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">عرض</span>
                                </a>`;
                            });
                            resultsHTML += '</div>';
                            orderSearchResults.html(resultsHTML);
                        } else {
                            orderSearchResults.html('<div class="p-3 text-center text-muted">لا توجد نتائج</div>');
                        }
                    } else {
                        orderSearchResults.hide();
                    }
                });
                
                // Handle clicking outside the search results to hide them
                $(document).on('click', function(e) {
                    if (!orderSearch.is(e.target) && !orderSearchResults.is(e.target) && orderSearchResults.has(e.target).length === 0) {
                        orderSearchResults.hide();
                    }
                });
                
                // Handle selecting an order from search results
                $(document).on('click', '.order-result', function(e) {
                    e.preventDefault();
                    const orderId = $(this).data('id');
                    
                    // Find and highlight the row
                    const table = $('.tab-pane.active table');
                    const row = table.find(`td:contains(${orderId})`).first().closest('tr');
                    
                    if (row.length) {
                        // Scroll to the row
                        $('html, body').animate({
                            scrollTop: row.offset().top - 100
                        }, 500);
                        
                        // Highlight the row
                        row.addClass('highlight-row');
                        setTimeout(function() {
                            row.removeClass('highlight-row');
                        }, 3000);
                    }
                    
                    // Hide search results
                    orderSearchResults.hide();
                });
            }
            
            // Initialize DataTables for all tables
            if ($.fn.DataTable) {
                $('.datatable').each(function() {
                    $(this).DataTable({
                        "language": {
                            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json"
                        },
                        "pageLength": 25,
                        "order": [[0, "desc"]],
                        "responsive": true
                    });
                });
            }
            
            // Payment method change
            $('#payment_method').on('change', function() {
                updateNotesPlaceholder($(this).val());
            });
            
            function updateNotesPlaceholder(paymentMethod) {
                let placeholder = 'ملاحظات إضافية (اختياري)';
                
                switch(paymentMethod) {
                    case 'بطاقة ائتمانية':
                        placeholder = 'رقم المعاملة أو معلومات البطاقة (اختياري)';
                        break;
                    case 'USDT':
                        placeholder = 'عنوان المحفظة أو معرف المعاملة (اختياري)';
                        break;
                    case 'Binance Pay':
                        placeholder = 'معرف معاملة Binance (اختياري)';
                        break;
                    case 'تحويل بنكي':
                        placeholder = 'تفاصيل التحويل البنكي (اختياري)';
                        break;
                    default:
                        placeholder = 'ملاحظات إضافية (اختياري)';
                }
                
                $('#description').attr('placeholder', placeholder);
            }
            
            // Payment approval/rejection confirmation
            $('.approve-payment-btn').on('click', function(e) {
                if (!confirm('هل أنت متأكد من اعتماد عملية الدفع هذه؟')) {
                    e.preventDefault();
                }
            });
            
            $('.reject-payment-btn').on('click', function(e) {
                if (!confirm('هل أنت متأكد من رفض عملية الدفع هذه؟')) {
                    e.preventDefault();
                }
            });
            
            // View receipt functionality
            $('.view-receipt').on('click', function() {
                const receiptUrl = $(this).data('receipt');
                $('#receiptImage').attr('src', receiptUrl);
                $('#downloadReceipt').attr('href', receiptUrl);
                $('#receiptModal').modal('show');
            });
            
            // Gift reason selection
            $('#gift_reason').on('change', function() {
                if ($(this).val() === 'أخرى') {
                    $('.custom-reason').show();
                    $('#custom_reason').prop('required', true);
                } else {
                    $('.custom-reason').hide();
                    $('#custom_reason').prop('required', false);
                }
            });
            
            // Handle status change for fields in orders page
            const statusSelects = document.querySelectorAll('select[id^="status"]');
            statusSelects.forEach(select => {
                const orderId = select.id.replace('status', '');
                const partialRemainsField = document.getElementById('partialRemains' + orderId);
                const startCountField = document.getElementById('startCountField' + orderId);
                
                select.addEventListener('change', function() {
                    if (this.value === 'partial') {
                        if (partialRemainsField) partialRemainsField.style.display = 'block';
                    } else {
                        if (partialRemainsField) partialRemainsField.style.display = 'none';
                    }
                    
                    if (this.value === 'processing') {
                        if (startCountField) startCountField.style.display = 'block';
                    } else {
                        if (startCountField) startCountField.style.display = 'none';
                    }
                });
                
                // Initialize visibility based on current value
                if (select.value === 'partial' && partialRemainsField) {
                    partialRemainsField.style.display = 'block';
                }
                
                if (select.value === 'processing' && startCountField) {
                    startCountField.style.display = 'block';
                }
            });
            
            // Handle refresh button for orders
            const refreshButton = document.getElementById('refreshOrders');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    location.reload();
                });
            }
            
            // Handle export to CSV for orders
            const exportButton = document.getElementById('exportOrdersCSV');
            if (exportButton) {
                exportButton.addEventListener('click', function() {
                    const activeTab = document.querySelector('.tab-pane.active');
                    const table = activeTab.querySelector('table');
                    
                    let csv = [];
                    const rows = table.querySelectorAll('tr');
                    
                    rows.forEach(row => {
                        const cols = row.querySelectorAll('td, th');
                        let rowText = [];
                        
                        cols.forEach((col, index) => {
                            // Skip the actions column
                            if (index !== cols.length - 1) {
                                let text = col.innerText.replace(/"/g, '""');
                                // Remove badge text for status column
                                if (index === 5 && row.querySelector('.badge')) {
                                    text = row.querySelector('.badge').innerText;
                                }
                                rowText.push('"' + text + '"');
                            }
                        });
                        
                        csv.push(rowText.join(','));
                    });
                    
                    // Download CSV file
                    const csvContent = csv.join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    const date = new Date().toISOString().slice(0, 10);
                    
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'orders_export_' + date + '.csv');
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
            
            // Add contains selector to jQuery
            jQuery.expr[':'].contains = function(a, i, m) {
                return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
            };
            
            // Additional CSS and fixes for users section only
            if (window.location.href.includes('section=users')) {
                $('<style>')
                    .prop('type', 'text/css')
                    .html(`
                        .section-users-container table {
                            width: 100% !important;
                            table-layout: fixed !important;
                            display: table !important;
                        }
                        .section-users-container table tr {
                            display: table-row !important;
                            width: 100% !important;
                        }
                        .section-users-container table th,
                        .section-users-container table td {
                            display: table-cell !important;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .section-users-container .dataTables_wrapper {
                            width: 100% !important;
                        }
                        #allUsersTable {
                            table-layout: fixed !important;
                            width: 100% !important;
                        }
                    `)
                    .appendTo('head');
                    
                // Force redraw of tables in users section
                setTimeout(function() {
                    if ($.fn.DataTable.isDataTable('#allUsersTable')) {
                        $('#allUsersTable').DataTable().columns.adjust().draw();
                    }
                }, 200);
            }
        });
    </script>
</body>
</html>