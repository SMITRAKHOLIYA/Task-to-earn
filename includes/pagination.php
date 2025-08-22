<?php
// pagination.php
// Universal pagination system for all admin/child pages

class Pagination {
    private $conn;
    private $records_per_page;
    private $current_page;
    private $total_records;
    private $total_pages;
    private $offset;
    private $base_url;

    public function __construct($conn, $records_per_page = 4) {
        $this->conn = $conn;
        $this->records_per_page = $records_per_page;
        $this->current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($this->current_page < 1) $this->current_page = 1;
        
        // Get base URL for pagination links
        $this->base_url = $this->getBaseUrl();
    }

    public function setup($sql, $params = [], $param_types = "") {
        // Get total records count
        $count_sql = $this->getCountSql($sql);
        $count_stmt = $this->conn->prepare($count_sql);
        
        if (!empty($params)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $this->total_records = $count_result->fetch_assoc()['total'];
        
        // Calculate total pages
        $this->total_pages = ceil($this->total_records / $this->records_per_page);
        
        // Adjust current page if it's beyond total pages
        if ($this->current_page > $this->total_pages && $this->total_pages > 0) {
            $this->current_page = $this->total_pages;
        }
        
        // Calculate offset
        $this->offset = ($this->current_page - 1) * $this->records_per_page;
        
        return [
            'offset' => $this->offset,
            'records_per_page' => $this->records_per_page,
            'current_page' => $this->current_page,
            'total_pages' => $this->total_pages,
            'total_records' => $this->total_records
        ];
    }

    public function render() {
        if ($this->total_pages <= 1) return '';
        
        ob_start();
        ?>
        <style>
            .pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 20px 0;
            }
            .pagination-controls {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .pagination-pages {
                display: flex;
                gap: 5px;
                margin: 0 10px;
            }
            .pagination-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: var(--glass-bg);
                color: var(--text);
                text-decoration: none;
                transition: all 0.2s;
            }
            .pagination-btn:hover:not(.disabled) {
                background: rgba(108, 92, 231, 0.2);
            }
            .pagination-btn.active {
                background: var(--primary);
                color: white;
            }
            .pagination-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
        <div class="pagination">
            <div class="pagination-controls">
                <?php if ($this->current_page > 1): ?>
                    <a href="<?php echo $this->base_url; ?>page=1" class="pagination-btn" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?php echo $this->base_url; ?>page=<?php echo $this->current_page - 1; ?>" class="pagination-btn" title="Previous Page">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>
                
                <div class="pagination-pages">
                    <?php
                    // Show page numbers with a limit to avoid too many buttons
                    $start_page = max(1, $this->current_page - 2);
                    $end_page = min($this->total_pages, $start_page + 4);
                    
                    // Adjust if we're near the end
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="<?php echo $this->base_url; ?>page=<?php echo $i; ?>" class="pagination-btn <?php echo $i == $this->current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <?php if ($this->current_page < $this->total_pages): ?>
                    <a href="<?php echo $this->base_url; ?>page=<?php echo $this->current_page + 1; ?>" class="pagination-btn" title="Next Page">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?php echo $this->base_url; ?>page=<?php echo $this->total_pages; ?>" class="pagination-btn" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function getBaseUrl() {
        $url = $_SERVER['PHP_SELF'];
        $query_params = $_GET;
        unset($query_params['page']);
        $query_string = http_build_query($query_params);
        $base_url = $url . ($query_string ? '?' . $query_string . '&' : '?');
        return $base_url;
    }

    private function getCountSql($sql) {
        // Convert the SQL to a count query
        if (stripos($sql, 'DISTINCT') !== false) {
            return "SELECT COUNT(*) as total FROM (" . $sql . ") as count_table";
        } else {
            // Simple approach for most cases
            $sql_lower = strtolower($sql);
            $from_pos = strpos($sql_lower, 'from');
            $order_by_pos = strpos($sql_lower, 'order by');
            
            if ($order_by_pos !== false) {
                $count_sql = "SELECT COUNT(*) as total " . substr($sql, $from_pos, $order_by_pos - $from_pos);
            } else {
                $count_sql = "SELECT COUNT(*) as total " . substr($sql, $from_pos);
            }
            
            return $count_sql;
        }
    }
}

// For direct usage without class
function setupPagination($conn, $sql, $params = [], $param_types = "", $records_per_page = 4) {
    $pagination = new Pagination($conn, $records_per_page);
    return $pagination->setup($sql, $params, $param_types);
}

function renderPagination() {
    global $pagination;
    if (isset($pagination)) {
        return $pagination->render();
    }
    return '';
}
?>```