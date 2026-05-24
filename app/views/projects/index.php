<?php
/**
 * View: projects/index.php
 * Variables: $projects, $stats, $fundingSources, $fieldsOfStudy, $years, $filters, $currentUser, $flash
 * $stats keys: total, total_budget, cnt_approved, cnt_in_progress, cnt_completed, cnt_closed, cnt_cancelled
 */
$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'superadmin']);

$statusMap = [
    'approved'    => ['primary',   'อนุมัติ'],
    'in_progress' => ['warning',   'กำลังดำเนินการ'],
    'completed'   => ['success',   'เสร็จสิ้น'],
    'closed'      => ['secondary', 'ปิดโครงการ'],
    'cancelled'   => ['danger',    'ยกเลิก'],
];
?>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : h($flash['type']) ?> alert-dismissible fade show">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#003B6D;">
            <i class="fas fa-flask me-2"></i>โครงการวิจัย
        </h4>
        <p class="text-muted mb-0 small">ทั้งหมด <strong><?= (int)($stats['total'] ?? count($projects ?? [])) ?></strong> โครงการ</p>
    </div>
    <?php if ($isAdmin): ?>
    <a href="/research/projects/create" class="btn text-white" style="background:#003B6D;">
        <i class="fas fa-plus-circle me-1"></i>เพิ่มโครงการใหม่
    </a>
    <?php endif; ?>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['key' => 'cnt_approved',    'label' => 'อนุมัติ',          'color' => 'primary', 'icon' => 'fa-check-circle'],
        ['key' => 'cnt_in_progress', 'label' => 'กำลังดำเนินการ',  'color' => 'warning',  'icon' => 'fa-sync'],
        ['key' => 'cnt_completed',   'label' => 'เสร็จสิ้น',        'color' => 'success',  'icon' => 'fa-trophy'],
        ['key' => 'cnt_cancelled',   'label' => 'ยกเลิก',           'color' => 'danger',   'icon' => 'fa-ban'],
    ];
    foreach ($statCards as $sc):
        $cnt = (int)($stats[$sc['key']] ?? 0);
    ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-<?= $sc['color'] ?> bg-opacity-10 p-3 flex-shrink-0"
                     style="width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas <?= $sc['icon'] ?> text-<?= $sc['color'] ?>"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-<?= $sc['color'] ?>"><?= $cnt ?></div>
                    <div class="small text-muted"><?= $sc['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Total budget banner -->
<?php if (!empty($stats['total_budget']) && $stats['total_budget'] > 0): ?>
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #003B6D!important;">
    <div class="card-body py-3 d-flex align-items-center gap-3">
        <i class="fas fa-coins fa-2x" style="color:#003B6D;opacity:.6;"></i>
        <div>
            <div class="small text-muted">งบประมาณรวมทั้งหมด</div>
            <div class="fs-5 fw-bold" style="color:#003B6D;"><?= formatBudget((float)$stats['total_budget']) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/research/projects">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">สถานะ</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกสถานะ --</option>
                        <?php foreach ($statusMap as $sv => [$sc, $sl]): ?>
                        <option value="<?= $sv ?>" <?= ($filters['status'] ?? '') === $sv ? 'selected' : '' ?>>
                            <?= $sl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">ปีงบประมาณ</label>
                    <select name="budget_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกปี --</option>
                        <?php foreach ($years as $yr): ?>
                        <option value="<?= h($yr['budget_year'] ?? $yr) ?>"
                            <?= ($filters['budget_year'] ?? '') == ($yr['budget_year'] ?? $yr) ? 'selected' : '' ?>>
                            <?= h($yr['budget_year'] ?? $yr) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">แหล่งทุน</label>
                    <select name="funding_source_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกแหล่งทุน --</option>
                        <?php foreach ($fundingSources as $fs): ?>
                        <option value="<?= (int)$fs['id'] ?>" <?= ($filters['funding_source_id'] ?? '') == $fs['id'] ? 'selected' : '' ?>>
                            <?= h($fs['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">ค้นหา</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="ชื่อโครงการ / รหัส"
                               value="<?= h($filters['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <?php if (array_filter($filters)): ?>
            <div class="mt-2">
                <a href="/research/projects" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>ล้างตัวกรอง
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="small text-muted">แสดง <?= count($projects ?? []) ?> โครงการ</span>
        <a href="/research/projects?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>"
           class="btn btn-sm btn-success">
            <i class="fas fa-file-excel me-1"></i>Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="projectsTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr style="background:#003B6D;color:#fff;">
                        <th class="ps-3">รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>หัวหน้า</th>
                        <th>แหล่งทุน</th>
                        <th class="text-end">งบอนุมัติ</th>
                        <th style="min-width:130px;">ความคืบหน้า</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                            ไม่พบข้อมูลโครงการวิจัย
                            <?php if ($isAdmin): ?>
                            <div class="mt-3">
                                <a href="/research/projects/create" class="btn btn-sm text-white" style="background:#003B6D;">
                                    <i class="fas fa-plus-circle me-1"></i>เพิ่มโครงการแรก
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p):
                        [$statusColor, $statusLabel] = $statusMap[$p['status'] ?? ''] ?? ['secondary', '-'];
                        $pct = (int)($p['progress_percentage'] ?? 0);
                        $barColor = $pct >= 100 ? 'success' : ($pct >= 60 ? 'primary' : ($pct >= 30 ? 'warning' : 'danger'));
                    ?>
                    <tr>
                        <td class="ps-3">
                            <a href="/research/projects/<?= (int)$p['id'] ?>"
                               class="fw-semibold text-decoration-none" style="color:#003B6D;">
                                <?= h($p['project_code'] ?? '-') ?>
                            </a>
                            <?php if (!empty($p['proposal_code'])): ?>
                            <br><small class="text-muted"><?= h($p['proposal_code']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold" style="max-width:240px;">
                                <?= h($p['title_th'] ?? '-') ?>
                            </div>
                            <?php if (!empty($p['field_name_th'])): ?>
                            <span class="badge bg-light text-dark border small mt-1"><?= h($p['field_name_th']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= h($p['pi_name'] ?? '-') ?></td>
                        <td class="small">
                            <?= h($p['funding_source_name'] ?? '-') ?>
                            <?php if (!empty($p['funding_source_type'])): ?>
                            <span class="badge bg-info bg-opacity-75 text-dark d-block mt-1" style="width:fit-content;">
                                <?= $p['funding_source_type'] === 'internal' ? 'ภายใน' : 'ภายนอก' ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end small fw-semibold">
                            <?= formatBudget((float)($p['approved_budget'] ?? 0)) ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-<?= $barColor ?>"
                                         style="width:<?= $pct ?>%"
                                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="small fw-bold text-<?= $barColor ?>"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                        </td>
                        <td class="text-center">
                            <a href="/research/projects/<?= (int)$p['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
#projectsTable thead th { white-space: nowrap; font-weight: 600; }
#projectsTable tbody tr:hover { background: rgba(0,59,109,.04); }
</style>

<script>
$(document).ready(function () {
    $('#projectsTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'ทั้งหมด']],
        order: [[0,'desc']],
        columnDefs: [
            { orderable: false, targets: [5,7] },
            { searchable: false, targets: [4,5,7] },
        ],
        dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip',
    });
});
</script>
