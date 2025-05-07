<?php
// admin/payments.php - Payment management for admins

// Get pending payments count for notification badge
$pending_count_query = "SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit' AND status = 'pending'";
$pending_count_result = $conn->query($pending_count_query);
$pending_payments = $pending_count_result ? $pending_count_result->fetch_assoc()['count'] : 0;

// Get payment stats
$total_deposits_query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'";
$total_deposits = $conn->query($total_deposits_query)->fetch_assoc()['total'] ?? 0;

$today_deposits_query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed' AND DATE(created_at) = CURDATE()";
$today_deposits = $conn->query($today_deposits_query)->fetch_assoc()['total'] ?? 0;

$month_deposits_query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$month_deposits = $conn->query($month_deposits_query)->fetch_assoc()['total'] ?? 0;

// Get counts for each payment type
$all_payments_count_query = "SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit'";
$all_payments_count = $conn->query($all_payments_count_query)->fetch_assoc()['count'] ?? 0;

$completed_payments_count_query = "SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit' AND status = 'completed'";
$completed_payments_count = $conn->query($completed_payments_count_query)->fetch_assoc()['count'] ?? 0;

$failed_payments_count_query = "SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit' AND (status = 'failed' OR status = 'cancelled')";
$failed_payments_count = $conn->query($failed_payments_count_query)->fetch_assoc()['count'] ?? 0;
?>

<!-- Payments Management -->
<div class="payments-section">
    <h1 class="mb-4">إدارة المدفوعات</h1>
    
    <?php if ($pending_payments > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        يوجد <strong><?php echo $pending_payments; ?></strong> عملية دفع بانتظار المراجعة.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Payment Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-info-light me-3">
                        <i class="fas fa-calendar text-info"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">إيداعات الشهر</h6>
                        <h3 class="card-title mb-0">$<?php echo number_format($month_deposits, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-success-light me-3">
                        <i class="fas fa-calendar-day text-success"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">إيداعات اليوم</h6>
                        <h3 class="card-title mb-0">$<?php echo number_format($today_deposits, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-primary-light me-3">
                        <i class="fas fa-dollar-sign text-primary"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">إجمالي الإيداعات</h6>
                        <h3 class="card-title mb-0">$<?php echo number_format($total_deposits, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs" id="paymentsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative" id="all-payments-tab" data-bs-toggle="tab" data-bs-target="#all-payments" type="button" role="tab" aria-controls="all-payments" aria-selected="true">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $all_payments_count; ?></span>
                        جميع المدفوعات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="pending-payments-tab" data-bs-toggle="tab" data-bs-target="#pending-payments" type="button" role="tab" aria-controls="pending-payments" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning"><?php echo $pending_payments; ?></span>
                        المدفوعات المعلقة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="completed-payments-tab" data-bs-toggle="tab" data-bs-target="#completed-payments" type="button" role="tab" aria-controls="completed-payments" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success"><?php echo $completed_payments_count; ?></span>
                        المدفوعات المكتملة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="failed-payments-tab" data-bs-toggle="tab" data-bs-target="#failed-payments" type="button" role="tab" aria-controls="failed-payments" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $failed_payments_count; ?></span>
                        المدفوعات الفاشلة
                    </button>
                </li>
            </ul>
            
            <div class="tab-content mt-4" id="paymentsTabContent">
                <!-- All Payments Tab -->
                <div class="tab-pane fade show active" id="all-payments" role="tabpanel" aria-labelledby="all-payments-tab">
                    <?php
                    $all_payments_query = "SELECT t.*, u.username 
                                          FROM transactions t 
                                          JOIN users u ON t.user_id = u.id 
                                          WHERE t.type = 'deposit' 
                                          ORDER BY t.created_at DESC";
                    $all_payments = $conn->query($all_payments_query);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($all_payments && $all_payments->num_rows > 0): ?>
                                <?php while ($payment = $all_payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $payment['user_id']; ?>">
                                            <?php echo htmlspecialchars($payment['username']); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $payment_method = '';
                                        
                                        if (strpos($payment['description'], 'بطاقة ائتمانية') !== false) {
                                            $payment_method = '<span class="badge bg-info">بطاقة ائتمانية</span>';
                                        } elseif (strpos($payment['description'], 'USDT') !== false) {
                                            $payment_method = '<span class="badge bg-success">USDT</span>';
                                        } elseif (strpos($payment['description'], 'Binance') !== false) {
                                            $payment_method = '<span class="badge bg-warning">Binance Pay</span>';
                                        } elseif (strpos($payment['description'], 'تحويل بنكي') !== false) {
                                            $payment_method = '<span class="badge bg-secondary">تحويل بنكي</span>';
                                        } elseif (strpos($payment['description'], 'الكريمي') !== false) {
                                            $payment_method = '<span class="badge bg-primary">بنك الكريمي</span>';
                                        } elseif (strpos($payment['description'], 'حوالة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-dark">حوالة محلية</span>';
                                        } elseif (strpos($payment['description'], 'محفظة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-info">محفظة محلية</span>';
                                        } elseif (strpos($payment['description'], 'هدية') !== false) {
                                            $payment_method = '<span class="badge bg-danger">هدية</span>';
                                        } else {
                                            $payment_method = '<span class="badge bg-light text-dark">أخرى</span>';
                                        }
                                        
                                        echo $payment_method;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($payment['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                $status_text = 'قيد الانتظار';
                                                break;
                                            case 'completed':
                                                $status_class = 'bg-success';
                                                $status_text = 'مكتمل';
                                                break;
                                            case 'failed':
                                                $status_class = 'bg-danger';
                                                $status_text = 'فشل';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-secondary';
                                                $status_text = 'ملغي';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <span title="<?php echo date('Y-m-d H:i:s', strtotime($payment['created_at'])); ?>">
                                            <?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($payment['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approvePaymentModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $payment['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                            
                                            <?php
                                            // Check if this payment has a receipt
                                            $receipt_query = "SELECT * FROM payment_receipts WHERE transaction_id = ?";
                                            $stmt = $conn->prepare($receipt_query);
                                            $stmt->bind_param("i", $payment['id']);
                                            $stmt->execute();
                                            $receipt_result = $stmt->get_result();
                                            
                                            if ($receipt_result && $receipt_result->num_rows > 0) {
                                                $receipt = $receipt_result->fetch_assoc();
                                                echo '<button type="button" class="btn btn-sm btn-secondary view-receipt" data-receipt="' . htmlspecialchars($receipt['file_path']) . '">
                                                    <i class="fas fa-file-image"></i>
                                                </button>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x mb-3 text-muted"></i>
                                        <p class="mb-0">لا توجد مدفوعات بعد</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pending Payments Tab -->
                <div class="tab-pane fade" id="pending-payments" role="tabpanel" aria-labelledby="pending-payments-tab">
                    <?php
                    $pending_payments_query = "SELECT t.*, u.username 
                                             FROM transactions t 
                                             JOIN users u ON t.user_id = u.id 
                                             WHERE t.type = 'deposit' AND t.status = 'pending' 
                                             ORDER BY t.created_at DESC";
                    $pending_payments_result = $conn->query($pending_payments_query);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_payments_result && $pending_payments_result->num_rows > 0): ?>
                                <?php while ($payment = $pending_payments_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $payment['user_id']; ?>">
                                            <?php echo htmlspecialchars($payment['username']); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $payment_method = '';
                                        
                                        if (strpos($payment['description'], 'بطاقة ائتمانية') !== false) {
                                            $payment_method = '<span class="badge bg-info">بطاقة ائتمانية</span>';
                                        } elseif (strpos($payment['description'], 'USDT') !== false) {
                                            $payment_method = '<span class="badge bg-success">USDT</span>';
                                        } elseif (strpos($payment['description'], 'Binance') !== false) {
                                            $payment_method = '<span class="badge bg-warning">Binance Pay</span>';
                                        } elseif (strpos($payment['description'], 'تحويل بنكي') !== false) {
                                            $payment_method = '<span class="badge bg-secondary">تحويل بنكي</span>';
                                        } elseif (strpos($payment['description'], 'الكريمي') !== false) {
                                            $payment_method = '<span class="badge bg-primary">بنك الكريمي</span>';
                                        } elseif (strpos($payment['description'], 'حوالة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-dark">حوالة محلية</span>';
                                        } elseif (strpos($payment['description'], 'محفظة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-info">محفظة محلية</span>';
                                        } else {
                                            $payment_method = '<span class="badge bg-light text-dark">أخرى</span>';
                                        }
                                        
                                        echo $payment_method;
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approvePaymentModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $payment['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                            
                                            <?php
                                            // Check if this payment has a receipt
                                            $receipt_query = "SELECT * FROM payment_receipts WHERE transaction_id = ?";
                                            $stmt = $conn->prepare($receipt_query);
                                            $stmt->bind_param("i", $payment['id']);
                                            $stmt->execute();
                                            $receipt_result = $stmt->get_result();
                                            
                                            if ($receipt_result && $receipt_result->num_rows > 0) {
                                                $receipt = $receipt_result->fetch_assoc();
                                                echo '<button type="button" class="btn btn-sm btn-secondary view-receipt" data-receipt="' . htmlspecialchars($receipt['file_path']) . '">
                                                    <i class="fas fa-file-image"></i>
                                                </button>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-check-circle fa-2x mb-3 text-success"></i>
                                        <p class="mb-0">لا توجد مدفوعات معلقة</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Completed Payments Tab -->
                <div class="tab-pane fade" id="completed-payments" role="tabpanel" aria-labelledby="completed-payments-tab">
                    <?php
                    $completed_payments_query = "SELECT t.*, u.username 
                                               FROM transactions t 
                                               JOIN users u ON t.user_id = u.id 
                                               WHERE t.type = 'deposit' AND t.status = 'completed' 
                                               ORDER BY t.created_at DESC";
                    $completed_payments = $conn->query($completed_payments_query);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($completed_payments && $completed_payments->num_rows > 0): ?>
                                <?php while ($payment = $completed_payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $payment['user_id']; ?>">
                                            <?php echo htmlspecialchars($payment['username']); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $payment_method = '';
                                        
                                        if (strpos($payment['description'], 'بطاقة ائتمانية') !== false) {
                                            $payment_method = '<span class="badge bg-info">بطاقة ائتمانية</span>';
                                        } elseif (strpos($payment['description'], 'USDT') !== false) {
                                            $payment_method = '<span class="badge bg-success">USDT</span>';
                                        } elseif (strpos($payment['description'], 'Binance') !== false) {
                                            $payment_method = '<span class="badge bg-warning">Binance Pay</span>';
                                        } elseif (strpos($payment['description'], 'تحويل بنكي') !== false) {
                                            $payment_method = '<span class="badge bg-secondary">تحويل بنكي</span>';
                                        } elseif (strpos($payment['description'], 'الكريمي') !== false) {
                                            $payment_method = '<span class="badge bg-primary">بنك الكريمي</span>';
                                        } elseif (strpos($payment['description'], 'حوالة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-dark">حوالة محلية</span>';
                                        } elseif (strpos($payment['description'], 'محفظة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-info">محفظة محلية</span>';
                                        } elseif (strpos($payment['description'], 'هدية') !== false) {
                                            $payment_method = '<span class="badge bg-danger">هدية</span>';
                                        } else {
                                            $payment_method = '<span class="badge bg-light text-dark">أخرى</span>';
                                        }
                                        
                                        echo $payment_method;
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $payment['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                            
                                            <?php
                                            // Check if this payment has a receipt
                                            $receipt_query = "SELECT * FROM payment_receipts WHERE transaction_id = ?";
                                            $stmt = $conn->prepare($receipt_query);
                                            $stmt->bind_param("i", $payment['id']);
                                            $stmt->execute();
                                            $receipt_result = $stmt->get_result();
                                            
                                            if ($receipt_result && $receipt_result->num_rows > 0) {
                                                $receipt = $receipt_result->fetch_assoc();
                                                echo '<button type="button" class="btn btn-sm btn-secondary view-receipt" data-receipt="' . htmlspecialchars($receipt['file_path']) . '">
                                                    <i class="fas fa-file-image"></i>
                                                </button>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x mb-3 text-muted"></i>
                                        <p class="mb-0">لا توجد مدفوعات مكتملة</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Failed Payments Tab -->
                <div class="tab-pane fade" id="failed-payments" role="tabpanel" aria-labelledby="failed-payments-tab">
                    <?php
                    $failed_payments_query = "SELECT t.*, u.username 
                                            FROM transactions t 
                                            JOIN users u ON t.user_id = u.id 
                                            WHERE t.type = 'deposit' AND (t.status = 'failed' OR t.status = 'cancelled') 
                                            ORDER BY t.created_at DESC";
                    $failed_payments = $conn->query($failed_payments_query);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($failed_payments && $failed_payments->num_rows > 0): ?>
                                <?php while ($payment = $failed_payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $payment['user_id']; ?>">
                                            <?php echo htmlspecialchars($payment['username']); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $payment_method = '';
                                        
                                        if (strpos($payment['description'], 'بطاقة ائتمانية') !== false) {
                                            $payment_method = '<span class="badge bg-info">بطاقة ائتمانية</span>';
                                        } elseif (strpos($payment['description'], 'USDT') !== false) {
                                            $payment_method = '<span class="badge bg-success">USDT</span>';
                                        } elseif (strpos($payment['description'], 'Binance') !== false) {
                                            $payment_method = '<span class="badge bg-warning">Binance Pay</span>';
                                        } elseif (strpos($payment['description'], 'تحويل بنكي') !== false) {
                                            $payment_method = '<span class="badge bg-secondary">تحويل بنكي</span>';
                                        } elseif (strpos($payment['description'], 'الكريمي') !== false) {
                                            $payment_method = '<span class="badge bg-primary">بنك الكريمي</span>';
                                        } elseif (strpos($payment['description'], 'حوالة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-dark">حوالة محلية</span>';
                                        } elseif (strpos($payment['description'], 'محفظة محلية') !== false) {
                                            $payment_method = '<span class="badge bg-info">محفظة محلية</span>';
                                        } else {
                                            $payment_method = '<span class="badge bg-light text-dark">أخرى</span>';
                                        }
                                        
                                        echo $payment_method;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = $payment['status'] === 'failed' ? 'bg-danger' : 'bg-secondary';
                                        $status_text = $payment['status'] === 'failed' ? 'فشل' : 'ملغي';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal<?php echo $payment['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $payment['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                            
                                            <?php
                                            // Check if this payment has a receipt
                                            $receipt_query = "SELECT * FROM payment_receipts WHERE transaction_id = ?";
                                            $stmt = $conn->prepare($receipt_query);
                                            $stmt->bind_param("i", $payment['id']);
                                            $stmt->execute();
                                            $receipt_result = $stmt->get_result();
                                            
                                            if ($receipt_result && $receipt_result->num_rows > 0) {
                                                $receipt = $receipt_result->fetch_assoc();
                                                echo '<button type="button" class="btn btn-sm btn-secondary view-receipt" data-receipt="' . htmlspecialchars($receipt['file_path']) . '">
                                                    <i class="fas fa-file-image"></i>
                                                </button>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-check-circle fa-2x mb-3 text-success"></i>
                                        <p class="mb-0">لا توجد مدفوعات فاشلة</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Funds Manually -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">إضافة رصيد يدوياً</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="userSearch" class="form-label">اسم المستخدم</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="userSearch" name="username" placeholder="ابحث عن مستخدم" required autocomplete="off">
                            <div id="searchResults" class="position-absolute w-100 bg-white border rounded shadow p-2 mt-1" style="display: none; z-index: 100; max-height: 200px; overflow-y: auto;"></div>
                            <div id="selected_user_info" class="mt-2" style="display: none;"></div>
                            <input type="hidden" id="user_id" name="user_id" value="">
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="amount" class="form-label">المبلغ (بالدولار)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" required>
                            <span class="input-group-text">$</span>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="payment_method" class="form-label">طريقة الدفع</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">-- اختر طريقة الدفع --</option>
                            <option value="بطاقة ائتمانية">بطاقة ائتمانية</option>
                            <option value="USDT">USDT</option>
                            <option value="Binance Pay">Binance Pay</option>
                            <option value="تحويل بنكي">تحويل بنكي</option>
                            <option value="بنك الكريمي">بنك الكريمي</option>
                            <option value="حوالة محلية">حوالة محلية</option>
                            <option value="محفظة محلية">محفظة محلية</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">ملاحظات (اختياري)</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="ملاحظات إضافية (اختياري)"></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="add_funds" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> إضافة الرصيد
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Payment Modals Container - All modals are extracted here -->
    <div id="payment-modals-container">
        <?php
        // Get ALL payments to create modals for them
        $all_modals_query = "SELECT t.*, u.username 
                        FROM transactions t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.type = 'deposit'
                        ORDER BY t.created_at DESC";
        $all_modals_result = $conn->query($all_modals_query);
        
        if ($all_modals_result && $all_modals_result->num_rows > 0):
            while ($payment = $all_modals_result->fetch_assoc()):
                // Determine payment method display
                $payment_method = '';
                if (strpos($payment['description'], 'بطاقة ائتمانية') !== false) {
                    $payment_method = '<span class="badge bg-info">بطاقة ائتمانية</span>';
                } elseif (strpos($payment['description'], 'USDT') !== false) {
                    $payment_method = '<span class="badge bg-success">USDT</span>';
                } elseif (strpos($payment['description'], 'Binance') !== false) {
                    $payment_method = '<span class="badge bg-warning">Binance Pay</span>';
                } elseif (strpos($payment['description'], 'تحويل بنكي') !== false) {
                    $payment_method = '<span class="badge bg-secondary">تحويل بنكي</span>';
                } elseif (strpos($payment['description'], 'الكريمي') !== false) {
                    $payment_method = '<span class="badge bg-primary">بنك الكريمي</span>';
                } elseif (strpos($payment['description'], 'حوالة محلية') !== false) {
                    $payment_method = '<span class="badge bg-dark">حوالة محلية</span>';
                } elseif (strpos($payment['description'], 'محفظة محلية') !== false) {
                    $payment_method = '<span class="badge bg-info">محفظة محلية</span>';
                } elseif (strpos($payment['description'], 'هدية') !== false) {
                    $payment_method = '<span class="badge bg-danger">هدية</span>';
                } else {
                    $payment_method = '<span class="badge bg-light text-dark">أخرى</span>';
                }
                
                // Determine status class and text
                $status_class = '';
                $status_text = '';
                
                switch ($payment['status']) {
                    case 'pending':
                        $status_class = 'bg-warning';
                        $status_text = 'قيد الانتظار';
                        break;
                    case 'completed':
                        $status_class = 'bg-success';
                        $status_text = 'مكتمل';
                        break;
                    case 'failed':
                        $status_class = 'bg-danger';
                        $status_text = 'فشل';
                        break;
                    case 'cancelled':
                        $status_class = 'bg-secondary';
                        $status_text = 'ملغي';
                        break;
                }
        ?>
        
        <!-- Payment Details Modal -->
        <div class="modal fade" id="paymentDetailsModal<?php echo $payment['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تفاصيل عملية الدفع #<?php echo $payment['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>رقم العملية:</strong> <?php echo $payment['id']; ?></p>
                                <p><strong>المستخدم:</strong> 
                                    <a href="admin.php?section=users&action=view&id=<?php echo $payment['user_id']; ?>">
                                        <?php echo htmlspecialchars($payment['username']); ?>
                                    </a>
                                </p>
                                <p><strong>المبلغ:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                                <p><strong>طريقة الدفع:</strong> <?php echo strip_tags($payment_method); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>الحالة:</strong> <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></p>
                                <p><strong>تاريخ الإنشاء:</strong> <?php echo date('Y-m-d H:i:s', strtotime($payment['created_at'])); ?></p>
                                <?php if (isset($payment['updated_at'])): ?>
                                <p><strong>آخر تحديث:</strong> <?php echo date('Y-m-d H:i:s', strtotime($payment['updated_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        <h6>الوصف:</h6>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($payment['description'])); ?>
                        </div>
                        
                        <?php
                        // Check if this payment has a receipt
                        $receipt_query = "SELECT * FROM payment_receipts WHERE transaction_id = ?";
                        $stmt = $conn->prepare($receipt_query);
                        $stmt->bind_param("i", $payment['id']);
                        $stmt->execute();
                        $receipt_result = $stmt->get_result();
                        
                        if ($receipt_result && $receipt_result->num_rows > 0):
                            $receipt = $receipt_result->fetch_assoc();
                        ?>
                        <hr>
                        <h6>إيصال الدفع:</h6>
                        <div class="text-center p-3 bg-light rounded">
                            <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" class="img-fluid border" style="max-height: 400px;">
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i> عرض الصورة كاملة
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        
                        <?php if ($payment['status'] === 'pending'): ?>
                        <form method="post" action="" class="d-inline">
                            <input type="hidden" name="transaction_id" value="<?php echo $payment['id']; ?>">
                            <button type="submit" name="approve_payment" class="btn btn-success approve-payment-btn">
                                <i class="fas fa-check me-1"></i> اعتماد
                            </button>
                        </form>
                        
                        <form method="post" action="" class="d-inline">
                            <input type="hidden" name="transaction_id" value="<?php echo $payment['id']; ?>">
                            <button type="submit" name="reject_payment" class="btn btn-danger reject-payment-btn">
                                <i class="fas fa-times me-1"></i> رفض
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="admin.php?section=notifications&user_id=<?php echo $payment['user_id']; ?>" class="btn btn-info">
                            <i class="fas fa-bell me-1"></i> إرسال إشعار
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Approve Payment Modal -->
        <?php if ($payment['status'] === 'pending'): ?>
        <div class="modal fade" id="approvePaymentModal<?php echo $payment['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">اعتماد عملية الدفع #<?php echo $payment['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            هل أنت متأكد من أنك تريد اعتماد عملية الدفع هذه؟
                        </div>
                        <div class="payment-details p-3 bg-light rounded mb-3">
                            <p><strong>المستخدم:</strong> <?php echo htmlspecialchars($payment['username']); ?></p>
                            <p><strong>المبلغ:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                            <p><strong>طريقة الدفع:</strong> <?php echo strip_tags($payment_method); ?></p>
                        </div>
                        <p class="text-success"><i class="fas fa-wallet me-1"></i> سيتم إضافة المبلغ $<?php echo number_format($payment['amount'], 2); ?> إلى رصيد المستخدم.</p>
                        
                        <form method="post" action="">
                            <input type="hidden" name="transaction_id" value="<?php echo $payment['id']; ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="approve_payment" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i> تأكيد الاعتماد
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reject Payment Modal -->
        <div class="modal fade" id="rejectPaymentModal<?php echo $payment['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">رفض عملية الدفع #<?php echo $payment['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            هل أنت متأكد من أنك تريد رفض عملية الدفع هذه؟
                        </div>
                        <div class="payment-details p-3 bg-light rounded mb-3">
                            <p><strong>المستخدم:</strong> <?php echo htmlspecialchars($payment['username']); ?></p>
                            <p><strong>المبلغ:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                            <p><strong>طريقة الدفع:</strong> <?php echo strip_tags($payment_method); ?></p>
                        </div>
                        
                        <form method="post" action="">
                            <input type="hidden" name="transaction_id" value="<?php echo $payment['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="rejection_reason<?php echo $payment['id']; ?>" class="form-label">سبب الرفض (اختياري):</label>
                                <textarea class="form-control" id="rejection_reason<?php echo $payment['id']; ?>" name="rejection_reason" rows="3" placeholder="أدخل سبب رفض عملية الدفع ليتم إرساله للمستخدم"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="reject_payment" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i> تأكيد الرفض
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
            endwhile;
        endif;
        ?>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">إيصال الدفع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="receiptImage" class="img-fluid" alt="إيصال الدفع">
            </div>
            <div class="modal-footer">
                <a href="" id="downloadReceipt" class="btn btn-primary" download>
                    <i class="fas fa-download me-1"></i> تحميل
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Enhanced user search functionality with real-time results
    const userSearchInput = $('#userSearch');
    const searchResults = $('#searchResults');
    const selectedUserInfo = $('#selected_user_info');
    const userIdInput = $('#user_id');
    
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
        selectedUserInfo.html(`
            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
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
    
    // Initialize DataTables
    $('.datatable').DataTable({
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json"
        },
        "order": [[0, "desc"]],
        "pageLength": 10
    });
    
    // View receipt functionality
    $(document).on('click', '.view-receipt', function() {
        const receiptUrl = $(this).data('receipt');
        $('#receiptImage').attr('src', receiptUrl);
        $('#downloadReceipt').attr('href', receiptUrl);
        $('#receiptModal').modal('show');
    });
});
</script>

<style>
/* Custom styles for the payments page */
.icon-box {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
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
.bg-info-light {
    background-color: rgba(23, 162, 184, 0.1);
}

.bg-success-light {
    background-color: rgba(40, 167, 69, 0.1);
}

.bg-primary-light {
    background-color: rgba(0, 123, 255, 0.1);
}

/* User search styling */
#searchResults {
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    z-index: 1050;
}

#searchResults .user-result {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

#searchResults .user-result:hover {
    background-color: #f8f9fa;
}

#searchResults .list-group {
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 0;
}

#selected_user_info {
    border: 1px solid #cfe2ff;
    background-color: #f0f7ff;
    border-radius: 0.25rem;
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

/* Stats icon */
.stats-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
}

/* Position badges on tabs */
.nav-link .badge {
    font-size: 0.75rem;
    margin-right: 0.5rem;
}

.nav-link .position-absolute {
    top: -8px !important;
    right: -8px !important;
}

/* Responsive table on small devices */
@media (max-width: 767.98px) {
    .table-responsive {
        border: 0;
    }
    
    .datatable {
        border: 0;
    }
    
    .datatable thead {
        display: none;
    }
    
    .datatable tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    
    .datatable tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 0;
        border-bottom: 1px solid #eee;
        padding: 0.75rem 1rem;
    }
    
    .datatable tbody td:last-child {
        border-bottom: 0;
    }
    
    .datatable tbody td:before {
        content: attr(data-label);
        font-weight: bold;
        margin-right: 0.5rem;
    }
}
</style>