<?php
// admin/orders.php - Order management for admins

// Get orders statistics for filters
$all_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$processing_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'];
$completed_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
$partial_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'partial'")->fetch_assoc()['count'];
$cancelled_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];
$failed_orders_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'failed'")->fetch_assoc()['count'];
?>

<!-- Orders Management -->
<div class="orders-section">
    <h1 class="mb-4">إدارة الطلبات</h1>
    
    <?php if ($pending_orders_count > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        يوجد <strong><?php echo $pending_orders_count; ?></strong> طلب بانتظار المراجعة.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Order Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-info-light me-3">
                        <i class="fas fa-shopping-cart text-info"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">إجمالي الطلبات</h6>
                        <h3 class="card-title mb-0"><?php echo number_format($all_orders_count); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-warning-light me-3">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">طلبات قيد الانتظار</h6>
                        <h3 class="card-title mb-0"><?php echo number_format($pending_orders_count); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box bg-success-light me-3">
                        <i class="fas fa-check text-success"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">طلبات مكتملة</h6>
                        <h3 class="card-title mb-0"><?php echo number_format($completed_orders_count); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="position-relative">
                        <input type="text" class="form-control" id="orderSearch" placeholder="ابحث باسم المستخدم أو الخدمة أو رقم الطلب...">
                        <div id="searchResults" class="position-absolute w-100 bg-white border rounded shadow p-2 mt-1" style="display: none; z-index: 100; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshOrders">
                            <i class="fas fa-sync-alt"></i> تحديث
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="exportOrdersCSV">
                            <i class="fas fa-file-export"></i> تصدير CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#orderStatsModal">
                            <i class="fas fa-chart-bar"></i> إحصائيات
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Statistics Modal -->
    <div class="modal fade" id="orderStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إحصائيات الطلبات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إجمالي الطلبات</h5>
                                    <h2 class="display-4"><?php echo number_format($all_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-warning bg-opacity-25">
                                <div class="card-body text-center">
                                    <h5 class="card-title">قيد الانتظار</h5>
                                    <h2 class="display-4"><?php echo number_format($pending_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-info bg-opacity-25">
                                <div class="card-body text-center">
                                    <h5 class="card-title">قيد التنفيذ</h5>
                                    <h2 class="display-4"><?php echo number_format($processing_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success bg-opacity-25">
                                <div class="card-body text-center">
                                    <h5 class="card-title">مكتملة</h5>
                                    <h2 class="display-4"><?php echo number_format($completed_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-primary bg-opacity-25">
                                <div class="card-body text-center">
                                    <h5 class="card-title">جزئية</h5>
                                    <h2 class="display-4"><?php echo number_format($partial_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-secondary bg-opacity-25">
                                <div class="card-body text-center">
                                    <h5 class="card-title">ملغية / فاشلة</h5>
                                    <h2 class="display-4"><?php echo number_format($cancelled_orders_count + $failed_orders_count); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Get revenue statistics
                    $total_revenue = $conn->query("SELECT SUM(amount) as total FROM orders WHERE status IN ('completed', 'partial')")->fetch_assoc()['total'] ?? 0;
                    $today_revenue = $conn->query("SELECT SUM(amount) as total FROM orders WHERE status IN ('completed', 'partial') AND DATE(updated_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
                    $month_revenue = $conn->query("SELECT SUM(amount) as total FROM orders WHERE status IN ('completed', 'partial') AND MONTH(updated_at) = MONTH(CURDATE()) AND YEAR(updated_at) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
                    ?>
                    
                    <hr>
                    <h5>إيرادات الطلبات</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success bg-opacity-10">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إجمالي الإيرادات</h5>
                                    <h2 class="display-6">$<?php echo number_format($total_revenue, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success bg-opacity-10">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إيرادات اليوم</h5>
                                    <h2 class="display-6">$<?php echo number_format($today_revenue, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success bg-opacity-10">
                                <div class="card-body text-center">
                                    <h5 class="card-title">إيرادات الشهر</h5>
                                    <h2 class="display-6">$<?php echo number_format($month_revenue, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs" id="ordersTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-orders" type="button" role="tab" aria-controls="all-orders" aria-selected="true">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $all_orders_count; ?></span>
                        جميع الطلبات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-orders" type="button" role="tab" aria-controls="pending-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning"><?php echo $pending_orders_count; ?></span>
                        قيد الانتظار
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="processing-tab" data-bs-toggle="tab" data-bs-target="#processing-orders" type="button" role="tab" aria-controls="processing-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info"><?php echo $processing_orders_count; ?></span>
                        قيد التنفيذ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button" role="tab" aria-controls="completed-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success"><?php echo $completed_orders_count; ?></span>
                        مكتملة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="partial-tab" data-bs-toggle="tab" data-bs-target="#partial-orders" type="button" role="tab" aria-controls="partial-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"><?php echo $partial_orders_count; ?></span>
                        جزئية
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-orders" type="button" role="tab" aria-controls="cancelled-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $cancelled_orders_count; ?></span>
                        ملغية
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed-orders" type="button" role="tab" aria-controls="failed-orders" aria-selected="false">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $failed_orders_count; ?></span>
                        فاشلة
                    </button>
                </li>
            </ul>
            
            <div class="tab-content mt-4" id="ordersTabContent">
                <!-- All Orders Tab -->
                <div class="tab-pane fade show active" id="all-orders" role="tabpanel" aria-labelledby="all-tab">
                    <?php
                    try {
                        $all_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                          FROM orders o 
                                          JOIN services s ON o.service_id = s.id 
                                          JOIN users u ON o.user_id = u.id 
                                          ORDER BY o.created_at DESC";
                        $all_orders = $conn->query($all_orders_query);
                        
                        if (!$all_orders) {
                            throw new Exception("Error executing orders query: " . $conn->error);
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger m-3">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</div>';
                        $all_orders = null;
                    }
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="allOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($all_orders && $all_orders->num_rows > 0): ?>
                                <?php while ($order = $all_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td>
                                        <?php if (isset($order['status']) && $order['status'] == 'partial'): ?>
                                        <span><?php echo number_format(isset($order['quantity']) && isset($order['remains']) ? ($order['quantity'] - $order['remains']) : 0); ?> / <?php echo number_format($order['quantity'] ?? 0); ?></span>
                                        <?php else: ?>
                                        <span><?php echo number_format($order['quantity'] ?? 0); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($order['status'] ?? '') {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                $status_text = 'قيد الانتظار';
                                                break;
                                            case 'processing':
                                                $status_class = 'bg-info';
                                                $status_text = 'قيد التنفيذ';
                                                break;
                                            case 'completed':
                                                $status_class = 'bg-success';
                                                $status_text = 'مكتمل';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-secondary';
                                                $status_text = 'ملغي';
                                                break;
                                            case 'failed':
                                                $status_class = 'bg-danger';
                                                $status_text = 'فشل';
                                                break;
                                            case 'partial':
                                                $status_class = 'bg-primary';
                                                $status_text = 'جزئي';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                                $status_text = 'غير معروف';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($order['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#startProcessingModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] === 'processing'): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#failOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد طلبات حتى الآن</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pending Orders Tab -->
                <div class="tab-pane fade" id="pending-orders" role="tabpanel" aria-labelledby="pending-tab">
                    <?php
                    try {
                        $pending_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                               FROM orders o 
                                               JOIN services s ON o.service_id = s.id 
                                               JOIN users u ON o.user_id = u.id 
                                               WHERE o.status = 'pending' 
                                               ORDER BY o.created_at DESC";
                        $pending_orders = $conn->query($pending_orders_query);
                        
                        if (!$pending_orders) {
                            throw new Exception("Error executing pending orders query: " . $conn->error);
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</div>';
                        $pending_orders = null;
                    }
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="pendingOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_orders && $pending_orders->num_rows > 0): ?>
                                <?php while ($order = $pending_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td><?php echo isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#startProcessingModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات معلقة</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Processing Orders Tab -->
                <div class="tab-pane fade" id="processing-orders" role="tabpanel" aria-labelledby="processing-tab">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="processingOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $processing_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                                              FROM orders o 
                                                              JOIN services s ON o.service_id = s.id 
                                                              JOIN users u ON o.user_id = u.id 
                                                              WHERE o.status = 'processing' 
                                                              ORDER BY o.created_at DESC";
                                    $processing_orders = $conn->query($processing_orders_query);
                                    
                                    if (!$processing_orders) {
                                        throw new Exception("Error executing processing orders query: " . $conn->error);
                                    }
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</div>';
                                    $processing_orders = null;
                                }
                                
                                if ($processing_orders && $processing_orders->num_rows > 0):
                                while ($order = $processing_orders->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td><?php echo isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#failOrderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات قيد التنفيذ</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Completed Orders Tab -->
                <div class="tab-pane fade" id="completed-orders" role="tabpanel" aria-labelledby="completed-tab">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="completedOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ الإكمال</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $completed_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                                            FROM orders o 
                                                            JOIN services s ON o.service_id = s.id 
                                                            JOIN users u ON o.user_id = u.id 
                                                            WHERE o.status = 'completed' 
                                                            ORDER BY o.updated_at DESC";
                                    $completed_orders = $conn->query($completed_orders_query);
                                    
                                    if (!$completed_orders) {
                                        throw new Exception("Error executing completed orders query: " . $conn->error);
                                    }
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</div>';
                                    $completed_orders = null;
                                }
                                
                                if ($completed_orders && $completed_orders->num_rows > 0): 
                                while ($order = $completed_orders->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td><?php echo isset($order['updated_at']) ? date('Y-m-d H:i', strtotime($order['updated_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات مكتملة</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Partial Orders Tab -->
                <div class="tab-pane fade" id="partial-orders" role="tabpanel" aria-labelledby="partial-tab">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="partialOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>المنفذ/الكمية</th>
                                    <th>المبلغ</th>
                                    <th>التقدم</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $partial_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                                        FROM orders o 
                                                        JOIN services s ON o.service_id = s.id 
                                                        JOIN users u ON o.user_id = u.id 
                                                        WHERE o.status = 'partial' 
                                                        ORDER BY o.updated_at DESC";
                                    $partial_orders = $conn->query($partial_orders_query);
                                    
                                    if (!$partial_orders) {
                                        throw new Exception("Error executing partial orders query: " . $conn->error);
                                    }
                                    
                                    if ($partial_orders && $partial_orders->num_rows > 0):
                                    while ($order = $partial_orders->fetch_assoc()):
                                        $progress = isset($order['quantity']) && isset($order['remains']) && $order['quantity'] > 0 
                                            ? round((($order['quantity'] - $order['remains']) / $order['quantity']) * 100)
                                            : 0;
                                ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format(($order['quantity'] - $order['remains']) ?? 0); ?> / <?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div>
                                        </div>
                                    </td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات جزئية</td>
                                </tr>
                                <?php
                                endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Cancelled Orders Tab -->
                <div class="tab-pane fade" id="cancelled-orders" role="tabpanel" aria-labelledby="cancelled-tab">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="cancelledOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ الإلغاء</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $cancelled_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                                             FROM orders o 
                                                             JOIN services s ON o.service_id = s.id 
                                                             JOIN users u ON o.user_id = u.id 
                                                             WHERE o.status = 'cancelled' 
                                                             ORDER BY o.updated_at DESC";
                                    $cancelled_orders = $conn->query($cancelled_orders_query);
                                    
                                    if (!$cancelled_orders) {
                                        throw new Exception("Error executing cancelled orders query: " . $conn->error);
                                    }
                                    
                                    if ($cancelled_orders && $cancelled_orders->num_rows > 0):
                                    while ($order = $cancelled_orders->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td><?php echo isset($order['updated_at']) ? date('Y-m-d H:i', strtotime($order['updated_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات ملغية</td>
                                </tr>
                                <?php
                                endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Failed Orders Tab -->
                <div class="tab-pane fade" id="failed-orders" role="tabpanel" aria-labelledby="failed-tab">
                    <div class="table-responsive">
                        <table class="table table-hover datatable-orders" id="failedOrdersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المستخدم</th>
                                    <th>الخدمة</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ الفشل</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $failed_orders_query = "SELECT o.*, s.name as service_name, u.username 
                                                          FROM orders o 
                                                          JOIN services s ON o.service_id = s.id 
                                                          JOIN users u ON o.user_id = u.id 
                                                          WHERE o.status = 'failed' 
                                                          ORDER BY o.updated_at DESC";
                                    $failed_orders = $conn->query($failed_orders_query);
                                    
                                    if (!$failed_orders) {
                                        throw new Exception("Error executing failed orders query: " . $conn->error);
                                    }
                                    
                                    if ($failed_orders && $failed_orders->num_rows > 0):
                                    while ($order = $failed_orders->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'] ?? 0); ?></td>
                                    <td>$<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                                    <td><?php echo isset($order['updated_at']) ? date('Y-m-d H:i', strtotime($order['updated_at'])) : ''; ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-bell"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد طلبات فاشلة</td>
                                </tr>
                                <?php
                                endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">خطأ في استرجاع البيانات: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Modals Container - All modals are extracted here -->
    <div id="order-modals-container">
        <?php
        // Get ALL orders to create modals for them
        $all_modals_query = "SELECT o.*, s.name as service_name, u.username 
                           FROM orders o 
                           JOIN services s ON o.service_id = s.id 
                           JOIN users u ON o.user_id = u.id 
                           ORDER BY o.created_at DESC";
        $all_modals_result = $conn->query($all_modals_query);
        
        if ($all_modals_result && $all_modals_result->num_rows > 0):
            while ($order = $all_modals_result->fetch_assoc()):
                // Determine status class and text
                $status_class = '';
                $status_text = '';
                
                switch ($order['status'] ?? '') {
                    case 'pending':
                        $status_class = 'bg-warning';
                        $status_text = 'قيد الانتظار';
                        break;
                    case 'processing':
                        $status_class = 'bg-info';
                        $status_text = 'قيد التنفيذ';
                        break;
                    case 'completed':
                        $status_class = 'bg-success';
                        $status_text = 'مكتمل';
                        break;
                    case 'cancelled':
                        $status_class = 'bg-secondary';
                        $status_text = 'ملغي';
                        break;
                    case 'failed':
                        $status_class = 'bg-danger';
                        $status_text = 'فشل';
                        break;
                    case 'partial':
                        $status_class = 'bg-primary';
                        $status_text = 'جزئي';
                        break;
                    default:
                        $status_class = 'bg-secondary';
                        $status_text = 'غير معروف';
                }
        ?>
        
        <!-- Order Update Modal -->
        <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="orderModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="orderModalLabel<?php echo $order['id']; ?>">تحديث حالة الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">المستخدم</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['username']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الخدمة</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['service_name']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الكمية</label>
                                <input type="text" class="form-control" value="<?php echo number_format($order['quantity'] ?? 0); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الرابط المستهدف</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($order['target_url'] ?? ''); ?>" readonly>
                                <div class="mt-2">
                                    <a href="<?php echo htmlspecialchars($order['target_url'] ?? '#'); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i> فتح الرابط
                                    </a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status<?php echo $order['id']; ?>" class="form-label">الحالة</label>
                                <select class="form-select" id="status<?php echo $order['id']; ?>" name="status" required>
                                    <option value="pending" <?php echo ($order['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                    <option value="processing" <?php echo ($order['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                                    <option value="completed" <?php echo ($order['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                                    <option value="partial" <?php echo ($order['status'] ?? '') === 'partial' ? 'selected' : ''; ?>>جزئي</option>
                                    <option value="cancelled" <?php echo ($order['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                    <option value="failed" <?php echo ($order['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>فشل</option>
                                </select>
                            </div>

                            <div class="mb-3 start-count-field" id="startCountField<?php echo $order['id']; ?>" style="display: <?php echo ($order['status'] ?? '') === 'processing' ? 'block' : 'none'; ?>;">
                                <label for="start_count<?php echo $order['id']; ?>" class="form-label">العدد الأولي</label>
                                <input type="number" class="form-control" id="start_count<?php echo $order['id']; ?>" name="start_count" min="0" value="<?php echo $order['start_count'] ?? 0; ?>">
                                <div class="form-text">أدخل العدد الأولي للمتابعين/المشاهدات قبل بدء الخدمة.</div>
                            </div>
                            
                            <div class="mb-3 partial-remains-field" id="partialRemains<?php echo $order['id']; ?>" style="display: <?php echo ($order['status'] ?? '') === 'partial' ? 'block' : 'none'; ?>;">
                                <label for="remains<?php echo $order['id']; ?>" class="form-label">الكمية المتبقية</label>
                                <input type="number" class="form-control" id="remains<?php echo $order['id']; ?>" name="remains" value="<?php echo $order['remains'] ?? 0; ?>" min="1" max="<?php echo isset($order['quantity']) ? $order['quantity'] - 1 : 0; ?>">
                                <div class="form-text">يجب أن تكون الكمية المتبقية أقل من الكمية الإجمالية.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-primary">تحديث الحالة</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Details Modal -->
        <div class="modal fade" id="orderDetailsModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="orderDetailsModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="orderDetailsModalLabel<?php echo $order['id']; ?>">تفاصيل الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>رقم الطلب:</strong> <?php echo $order['id']; ?></p>
                                <p><strong>المستخدم:</strong> <a href="admin.php?section=users&action=view&id=<?php echo $order['user_id']; ?>"><?php echo htmlspecialchars($order['username']); ?></a></p>
                                <p><strong>الخدمة:</strong> <?php echo htmlspecialchars($order['service_name']); ?></p>
                                <p><strong>الكمية:</strong> <?php echo number_format($order['quantity'] ?? 0); ?></p>
                                <p><strong>المبلغ:</strong> $<?php echo number_format($order['amount'] ?? 0, 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>الحالة:</strong> <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></p>
                                <p><strong>تاريخ الطلب:</strong> <?php echo isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : ''; ?></p>
                                <p><strong>آخر تحديث:</strong> <?php echo isset($order['updated_at']) ? date('Y-m-d H:i', strtotime($order['updated_at'])) : ''; ?></p>
                                <p><strong>الرابط المستهدف:</strong> <a href="<?php echo htmlspecialchars($order['target_url'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($order['target_url'] ?? ''); ?></a></p>
                                <p><strong>العدد الأولي:</strong> <?php echo number_format($order['start_count'] ?? 0); ?></p>
                                
                                <?php if (isset($order['status']) && $order['status'] === 'partial'): ?>
                                <p><strong>المتبقي:</strong> <?php echo number_format($order['remains'] ?? 0); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($order['status']) && ($order['status'] === 'partial' || $order['status'] === 'processing')): ?>
                        <div class="mt-4">
                            <h6>تقدم الطلب</h6>
                            <div class="progress">
                                <?php 
                                $progress = 0;
                                if ($order['status'] === 'partial' && isset($order['quantity']) && isset($order['remains']) && $order['quantity'] > 0) {
                                    $progress = round((($order['quantity'] - $order['remains']) / $order['quantity']) * 100);
                                } elseif ($order['status'] === 'processing') {
                                    $progress = 50; // Processing is halfway
                                } elseif ($order['status'] === 'completed') {
                                    $progress = 100;
                                }
                                ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>" data-bs-dismiss="modal">
                            <i class="fas fa-edit me-1"></i> تحديث الحالة
                        </button>
                        <a href="admin.php?section=notifications&user_id=<?php echo $order['user_id']; ?>" class="btn btn-warning">
                            <i class="fas fa-bell me-1"></i> إرسال إشعار
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Start Processing Modal -->
        <div class="modal fade" id="startProcessingModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">بدء تنفيذ الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>هل أنت متأكد من أنك تريد بدء تنفيذ هذا الطلب؟</p>
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="processing">
                            
                            <div class="mb-3">
                                <label for="start_count_modal<?php echo $order['id']; ?>" class="form-label">العدد الأولي</label>
                                <input type="number" class="form-control" id="start_count_modal<?php echo $order['id']; ?>" name="start_count" min="0" value="0">
                                <div class="form-text">أدخل العدد الأولي للمتابعين/المشاهدات قبل بدء الخدمة.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-success">بدء التنفيذ</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cancel Order Modal -->
        <div class="modal fade" id="cancelOrderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">إلغاء الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>هل أنت متأكد من أنك تريد إلغاء هذا الطلب؟ سيتم استرداد المبلغ للمستخدم.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            سيتم إعادة مبلغ $<?php echo number_format($order['amount'] ?? 0, 2); ?> إلى رصيد المستخدم <?php echo htmlspecialchars($order['username']); ?>.
                        </div>
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="cancelled">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-danger">تأكيد الإلغاء</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Complete Order Modal -->
        <div class="modal fade" id="completeOrderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">إكمال الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>هل أنت متأكد من أنك تريد تعيين حالة هذا الطلب كمكتمل؟</p>
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="completed">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-success">تأكيد الإكمال</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Partial Order Modal -->
        <div class="modal fade" id="partialOrderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تسليم جزئي للطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>استخدم هذا الخيار إذا لم تتمكن من إكمال الطلب بالكامل. سيتم استرداد المبلغ المتبقي للمستخدم.</p>
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="partial">
                            
                            <div class="mb-3">
                                <label for="partial_remains<?php echo $order['id']; ?>" class="form-label">الكمية المتبقية</label>
                                <input type="number" class="form-control" id="partial_remains<?php echo $order['id']; ?>" name="remains" min="1" max="<?php echo isset($order['quantity']) ? $order['quantity'] - 1 : 0; ?>" required>
                                <div class="form-text">أدخل عدد الوحدات التي لم يتم تسليمها. يجب أن تكون أقل من إجمالي الكمية (<?php echo number_format($order['quantity'] ?? 0); ?>).</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-primary">تأكيد التسليم الجزئي</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fail Order Modal -->
        <div class="modal fade" id="failOrderModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">فشل الطلب #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>هل أنت متأكد من أنك تريد تعيين حالة هذا الطلب كفاشل؟ سيتم استرداد المبلغ للمستخدم.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            سيتم إعادة مبلغ $<?php echo number_format($order['amount'] ?? 0, 2); ?> إلى رصيد المستخدم <?php echo htmlspecialchars($order['username']); ?>.
                        </div>
                        <form method="post" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="failed">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_order_status" class="btn btn-danger">تأكيد الفشل</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        endif;
        ?>
    </div>
</div>

<!-- JavaScript for handling orders page functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle status change for fields
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
    
    // Enhanced order search functionality
    const orderSearch = document.getElementById('orderSearch');
    const searchResults = document.getElementById('searchResults');
    
    if (orderSearch) {
        orderSearch.addEventListener('input', function() {
            const searchTerm = this.value;
            
            // Only search if at least 2 characters
            if (searchTerm.length >= 2) {
                // Show loading indicator
                searchResults.innerHTML = '<div class="p-2 text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</div>';
                searchResults.style.display = 'block';
                
                // Perform search on all visible rows in active tab
                const activeTab = document.querySelector('.tab-pane.active');
                const rows = activeTab.querySelectorAll('tbody tr');
                let matches = [];
                
                rows.forEach(row => {
                    const id = row.cells[0].textContent.toLowerCase();
                    const username = row.cells[1].textContent.toLowerCase();
                    const service = row.cells[2].textContent.toLowerCase();
                    
                    if (id.includes(searchTerm.toLowerCase()) || 
                        username.includes(searchTerm.toLowerCase()) || 
                        service.includes(searchTerm.toLowerCase())) {
                        matches.push({
                            id: row.cells[0].textContent,
                            username: row.cells[1].textContent.trim(),
                            service: row.cells[2].textContent.trim()
                        });
                    }
                });
                
                // Display results
                if (matches.length > 0) {
                    let resultsHTML = '<div class="list-group">';
                    matches.forEach(match => {
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
                    searchResults.innerHTML = resultsHTML;
                } else {
                    searchResults.innerHTML = '<div class="p-3 text-center text-muted">لا توجد نتائج</div>';
                }
            } else {
                searchResults.style.display = 'none';
            }
        });
        
        // Handle clicking outside the search results to hide them
        document.addEventListener('click', function(e) {
            if (!orderSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Handle selecting an order from search results
        document.addEventListener('click', function(e) {
            if (e.target.closest('.order-result')) {
                e.preventDefault();
                const orderId = e.target.closest('.order-result').getAttribute('href').replace('#order_', '');
                
                // Find the row with this order ID
                const activeTab = document.querySelector('.tab-pane.active');
                const orderRow = activeTab.querySelector(`tr td:first-child:contains('${orderId}')`);
                
                if (orderRow) {
                    const row = orderRow.closest('tr');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('highlight-row');
                    setTimeout(() => {
                        row.classList.remove('highlight-row');
                    }, 3000);
                }
                
                searchResults.style.display = 'none';
            }
        });
    }
    
    // Handle refresh button
    const refreshButton = document.getElementById('refreshOrders');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            location.reload();
        });
    }
    
    // Handle export to CSV
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

    // Initialize DataTables
    function initializeDataTable(tableId) {
        const table = document.getElementById(tableId);
        if (table && typeof $.fn.DataTable !== 'undefined') {
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#' + tableId)) {
                $('#' + tableId).DataTable().destroy();
            }
            
            // Initialize DataTable
            $('#' + tableId).DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json"
                },
                "pageLength": 25,
                "order": [[0, "desc"]],
                "responsive": true
            });
        }
    }
    
    // Initialize DataTable for active tab on page load
    initializeDataTable('allOrdersTable');
    
    // Initialize DataTables when switching tabs
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target').replace('#', '');
            const tableId = targetId.replace('-orders', 'OrdersTable');
            initializeDataTable(tableId);
        });
    });
    
    // Add JQuery extension method to find elements containing text
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };
});
</script>

<style>
/* Custom styles for the orders page */
.icon-box {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

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

.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1);
}

.bg-primary-light {
    background-color: rgba(0, 123, 255, 0.1);
}

/* User search styling */
#searchResults {
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    z-index: 1050;
}

#searchResults .order-result {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

#searchResults .order-result:hover {
    background-color: #f8f9fa;
}

#searchResults .list-group {
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 0;
}

/* Highlight selected row */
.highlight-row {
    background-color: rgba(0, 123, 255, 0.2) !important;
    transition: background-color 1s ease;
}

/* Better looking tables */
.datatable-orders thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.datatable-orders tbody tr:hover {
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

/* Position badges on tabs */
.nav-link .badge {
    font-size: 0.75rem;
    margin-right: 0.5rem;
}

/* Responsive table on small devices */
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
</style>
