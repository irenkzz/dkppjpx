<?php
// Audit Log viewer (read-only)
if (empty($_SESSION['namauser']) && empty($_SESSION['passuser'])) {
    echo "<script>alert('Untuk mengakses modul, Anda harus login dulu.'); window.location = '../../index.php'</script>";
} else {
    require_once __DIR__ . "/../../includes/bootstrap.php";
    require_admin_login();

    if (($_SESSION['leveluser'] ?? '') !== 'admin') {
        echo "<p>Access denied</p>";
        exit;
    }

    $limit  = 50;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $rawFrom = trim(strip_tags($_GET['from'] ?? ''));
    $rawTo   = trim(strip_tags($_GET['to'] ?? ''));
    $from    = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom)) ? $rawFrom : '';
    $to      = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)) ? $rawTo : '';
    $userProvidedDate = ($rawFrom !== '' || $rawTo !== '');
    $applyDateRange   = $userProvidedDate;
    $usedDefaultRange = false;

    if (!$userProvidedDate) {
        $from = date('Y-m-d', strtotime('-7 days'));
        $to   = date('Y-m-d');
        $applyDateRange   = true;
        $usedDefaultRange = true;
    }

    $usernameFilter = substr(trim(strip_tags($_GET['username'] ?? '')), 0, 100);
    $moduleFilter   = substr(trim(strip_tags($_GET['module_filter'] ?? '')), 0, 100);
    $actionFilter   = substr(trim(strip_tags($_GET['action'] ?? '')), 0, 50);
    $qFilter        = substr(trim(strip_tags($_GET['q'] ?? '')), 0, 100);
    $idLog          = isset($_GET['id_log']) ? (int)$_GET['id_log'] : 0;

    $conditions = array();
    $types      = '';
    $params     = array();

    $buildFilters = function ($useDateRange) use ($from, $to, $usernameFilter, $moduleFilter, $actionFilter, $qFilter) {
        $conditions = array();
        $types      = '';
        $params     = array();

        if ($useDateRange && $from !== '') {
            $conditions[] = "created_at >= ?";
            $types       .= 's';
            $params[]     = $from . ' 00:00:00';
        }

        if ($useDateRange && $to !== '') {
            $conditions[] = "created_at <= ?";
            $types       .= 's';
            $params[]     = $to . ' 23:59:59';
        }

        if ($usernameFilter !== '') {
            $conditions[] = "username = ?";
            $types       .= 's';
            $params[]     = $usernameFilter;
        }

        if ($moduleFilter !== '') {
            $conditions[] = "module = ?";
            $types       .= 's';
            $params[]     = $moduleFilter;
        }

        if ($actionFilter !== '') {
            $conditions[] = "action = ?";
            $types       .= 's';
            $params[]     = $actionFilter;
        }

        if ($qFilter !== '') {
            $like         = '%' . $qFilter . '%';
            $conditions[] = "(message LIKE ? OR entity_id LIKE ?)";
            $types       .= 'ss';
            $params[]     = $like;
            $params[]     = $like;
        }

        return array($conditions, $types, $params);
    };

    list($conditions, $types, $params) = $buildFilters($applyDateRange);

    $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $dataSql    = "SELECT id_log, created_at, username, user_level, module, action, entity, entity_id, message, ip_address FROM audit_log {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $dataTypes  = $types . 'ii';
    $dataParams = array_merge($params, array($limit, $offset));
    $logs       = querydb_prepared($dataSql, $dataTypes, $dataParams);

    $countSql   = "SELECT COUNT(*) AS cnt FROM audit_log {$whereSql}";
    $countRes   = querydb_prepared($countSql, $types, $params);
    $countRow   = $countRes ? $countRes->fetch_assoc() : array('cnt' => 0);
    $totalRows  = (int)($countRow['cnt'] ?? 0);
    $totalPages = max(1, (int)ceil($totalRows / $limit));

    // Fallback: if default 7-day window yields no rows, drop date filter to show recent rows
    if ($totalRows === 0 && $usedDefaultRange) {
        $applyDateRange = false;
        list($conditions, $types, $params) = $buildFilters($applyDateRange);
        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $dataSql    = "SELECT id_log, created_at, username, user_level, module, action, entity, entity_id, message, ip_address FROM audit_log {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $dataTypes  = $types . 'ii';
        $dataParams = array_merge($params, array($limit, $offset));
        $logs       = querydb_prepared($dataSql, $dataTypes, $dataParams);

        $countSql   = "SELECT COUNT(*) AS cnt FROM audit_log {$whereSql}";
        $countRes   = querydb_prepared($countSql, $types, $params);
        $countRow   = $countRes ? $countRes->fetch_assoc() : array('cnt' => 0);
        $totalRows  = (int)($countRow['cnt'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRows / $limit));
    }

    $modulesList = querydb("SELECT DISTINCT module FROM audit_log WHERE module IS NOT NULL AND module <> '' ORDER BY module");
    $actionsList = querydb("SELECT DISTINCT action FROM audit_log WHERE action IS NOT NULL AND action <> '' ORDER BY action");

    $formFrom = $applyDateRange ? $from : '';
    $formTo   = $applyDateRange ? $to : '';

    $baseFilters = array(
        'from'     => $applyDateRange ? $from : '',
        'to'       => $applyDateRange ? $to : '',
        'username' => $usernameFilter,
        'module_filter'   => $moduleFilter,
        'action'   => $actionFilter,
        'q'        => $qFilter,
    );

    if ($page > 1) {
        $baseFilters['page'] = $page;
    }

    $filterQuery = http_build_query(array_filter($baseFilters, function ($v) {
        return $v !== '' && $v !== null;
    }));

    function auditlog_truncate($text, $len = 80)
    {
        $text = (string)$text;
        if (strlen($text) > $len) {
            return substr($text, 0, $len - 3) . '...';
        }
        return $text;
    }
    ?>
    <section class="content-header">
        <h1>Audit Log</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-solid">
                    <div class="box-body">
                        <form method="get" class="form-inline">
                            <input type="hidden" name="module" value="auditlog" />
                            <div class="form-group">
                                <label for="from">From</label>
                                <input type="date" class="form-control" id="from" name="from" value="<?php echo e($formFrom); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="to">To</label>
                                <input type="date" class="form-control" id="to" name="to" value="<?php echo e($formTo); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo e($usernameFilter); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="module">Module</label>
                                <select class="form-control" id="module" name="module_filter">
                                    <option value="">-- any --</option>
                                    <?php
                                    if ($modulesList) {
                                        while ($m = $modulesList->fetch_assoc()) {
                                            $val  = $m['module'];
                                            $sel  = ($val === $moduleFilter) ? ' selected' : '';
                                            echo '<option value="' . e($val) . '"' . $sel . '>' . e($val) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="action">Action</label>
                                <select class="form-control" id="action" name="action">
                                    <option value="">-- any --</option>
                                    <?php
                                    if ($actionsList) {
                                        while ($a = $actionsList->fetch_assoc()) {
                                            $val = $a['action'];
                                            $sel = ($val === $actionFilter) ? ' selected' : '';
                                            echo '<option value="' . e($val) . '"' . $sel . '>' . e($val) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="q">Keyword</label>
                                <input type="text" class="form-control" id="q" name="q" value="<?php echo e($qFilter); ?>" placeholder="message or entity id" />
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php
        if ($idLog > 0) {
            $detailRes = querydb_prepared("SELECT * FROM audit_log WHERE id_log = ? LIMIT 1", "i", array($idLog));
            $detailRow = $detailRes ? $detailRes->fetch_assoc() : null;
            ?>
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">Detail Log #<?php echo e($idLog); ?></h3>
                            <div class="box-tools pull-right">
                                <?php
                                $backLink = '?module=auditlog' . ($filterQuery ? '&' . $filterQuery : '');
                                ?>
                                <a class="btn btn-default btn-sm" href="<?php echo $backLink; ?>">Back to list</a>
                            </div>
                        </div>
                        <div class="box-body">
                            <?php if ($detailRow) { ?>
                                <p><strong>Time:</strong> <?php echo e($detailRow['created_at']); ?></p>
                                <p><strong>User:</strong> <?php echo e($detailRow['username']); ?> (<?php echo e($detailRow['user_level']); ?>)</p>
                                <p><strong>Module/Action:</strong> <?php echo e($detailRow['module']); ?> / <?php echo e($detailRow['action']); ?></p>
                                <p><strong>Entity:</strong> <?php echo e($detailRow['entity']); ?> (<?php echo e($detailRow['entity_id']); ?>)</p>
                                <p><strong>Message:</strong> <?php echo e($detailRow['message']); ?></p>
                                <p><strong>URL:</strong> <?php echo e($detailRow['url']); ?></p>
                                <p><strong>IP/User Agent:</strong> <?php echo e($detailRow['ip_address']); ?> / <?php echo e($detailRow['user_agent']); ?></p>
                                <p><strong>Before:</strong></p>
                                <pre><?php echo htmlspecialchars($detailRow['before_json'] ?? '', ENT_QUOTES, 'UTF-8'); ?></pre>
                                <p><strong>After:</strong></p>
                                <pre><?php echo htmlspecialchars($detailRow['after_json'] ?? '', ENT_QUOTES, 'UTF-8'); ?></pre>
                                <p><strong>Extra:</strong></p>
                                <pre><?php echo htmlspecialchars($detailRow['extra_json'] ?? '', ENT_QUOTES, 'UTF-8'); ?></pre>
                            <?php } else { ?>
                                <p>Data tidak ditemukan.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Entity ID</th>
                                    <th>Message</th>
                                    <th>IP</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($logs && $logs->num_rows > 0) {
                                while ($row = $logs->fetch_assoc()) {
                                    $detailParams          = $baseFilters;
                                    $detailParams['id_log'] = $row['id_log'];
                                    $detailLink            = '?module=auditlog&' . http_build_query(array_filter($detailParams, function ($v) {
                                        return $v !== '' && $v !== null;
                                    }));
                                    ?>
                                    <tr>
                                        <td><?php echo e($row['created_at']); ?></td>
                                        <td><?php echo e($row['username']); ?></td>
                                        <td><?php echo e($row['user_level']); ?></td>
                                        <td><?php echo e($row['module']); ?></td>
                                        <td><?php echo e($row['action']); ?></td>
                                        <td><?php echo e($row['entity']); ?></td>
                                        <td><?php echo e($row['entity_id']); ?></td>
                                        <td><?php echo e(auditlog_truncate($row['message'])); ?></td>
                                        <td><?php echo e($row['ip_address']); ?></td>
                                        <td><a class="btn btn-xs btn-default" href="<?php echo $detailLink; ?>">Detail</a></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="10">Tidak ada data.</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-left">
                            Menampilkan <?php echo e($logs ? $logs->num_rows : 0); ?> dari <?php echo e($totalRows); ?> log.
                        </div>
                        <div class="pull-right">
                            <?php
                            if ($totalPages > 1) {
                                echo '<ul class="pagination" style="margin:0;">';
                                $baseLink = '?module=auditlog';
                                $filterBase = $baseFilters;
                                unset($filterBase['page']);
                                $baseQueryNoPage = http_build_query(array_filter($filterBase, function ($v) {
                                    return $v !== '' && $v !== null;
                                }));
                                for ($p = 1; $p <= $totalPages; $p++) {
                                    $queryParts = $baseQueryNoPage ? $baseQueryNoPage . '&page=' . $p : 'page=' . $p;
                                    $link       = $baseLink . '&' . $queryParts;
                                    $active     = ($p === $page) ? ' class="active"' : '';
                                    echo '<li' . $active . '><a href="' . $link . '">' . $p . '</a></li>';
                                }
                                echo '</ul>';
                            }
                            ?>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php
}
