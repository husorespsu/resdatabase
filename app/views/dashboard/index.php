<?php
/**
 * View: dashboard/index.php — Executive Dashboard
 * Rendered inside layouts/main.php (no standalone HTML here)
 *
 * Variables from DashboardController::executive():
 * @var array  $kpis            total_proposals, total_budget, reviewing, approved, in_progress, closed
 * @var array  $byFunding       [{funding_name, funding_type, total, total_budget}]
 * @var array  $byType          [{funding_type, total}]
 * @var array  $monthSeries     {labels:[], values:[]}
 * @var array  $byField         [{field_name, total_budget}]
 * @var array  $statusYearPivot {labels:[], series:[{name, data:[]}]}
 * @var array  $summary         [{funding_name, type, total, approved, reviewing, rejected, total_budget}]
 * @var array  $filterYears     [2567, 2566, ...]
 * @var array  $filterFields    [{id, name_th}]
 * @var array  $selectedYears
 * @var array  $selectedMonths
 * @var string $selectedField
 * @var string $selectedFunding
 */

$thaiMonthNames = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม',   6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม',  9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
];

// Build chart JSON
$chartFundingLabels  = json_encode(array_column($byFunding ?? [], 'funding_name'), JSON_UNESCAPED_UNICODE);
$chartFundingValues  = json_encode(array_column($byFunding ?? [], 'total'));

$internalTotal = 0; $externalTotal = 0;
foreach ($byType ?? [] as $row) {
    if (($row['funding_type'] ?? '') === 'internal')  $internalTotal = (int)$row['total'];
    if (($row['funding_type'] ?? '') === 'external')  $externalTotal = (int)$row['total'];
}
$chartTypeLabels = json_encode(['ทุนภายใน', 'ทุนภายนอก'], JSON_UNESCAPED_UNICODE);
$chartTypeValues = json_encode([$internalTotal, $externalTotal]);

$chartMonthLabels = json_encode($monthSeries['labels'] ?? [], JSON_UNESCAPED_UNICODE);
$chartMonthValues = json_encode($monthSeries['values'] ?? []);

$chartFieldLabels  = json_encode(array_column($byField ?? [], 'field_name'), JSON_UNESCAPED_UNICODE);
$chartFieldBudgets = json_encode(
    array_map(fn($r) => round((float)($r['total_budget'] ?? 0) / 1000000, 2), $byField ?? [])
);

$stackedLabels   = json_encode($statusYearPivot['labels'] ?? []);
$stackedDatasets = json_encode($statusYearPivot['series'] ?? [], JSON_UNESCAPED_UNICODE);
?>

<style>
    .kpi-card {
        border-radius: .75rem; padding: 1.25rem; color: #fff;
        position: relative; overflow: hidden; border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    .kpi-card .kpi-bg-icon {
        position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
        font-size: 2.5rem; opacity: .18;
    }
    .kpi-card .kpi-number { font-size: 2rem; font-weight: 800; line-height: 1; }
    .kpi-card .kpi-label  { font-size: .82rem; opacity: .88; margin-top: .3rem; }
    .kpi-card .kpi-sub    { font-size: .72rem; opacity: .7; margin-top: .2rem; }
    .kpi-primary   { background: linear-gradient(135deg, #003B6D, #005099); }
    .kpi-success   { background: linear-gradient(135deg, #059669, #10b981); }
    .kpi-warning   { background: linear-gradient(135deg, #d97706, #f59e0b); }
    .kpi-info      { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
    .kpi-orange    { background: linear-gradient(135deg, #ea580c, #f97316); }
    .kpi-secondary { background: linear-gradient(135deg, #475569, #64748b); }

    .chart-card {
        background: #fff; border-radius: .75rem; padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.06); height: 100%;
    }
    .chart-card .chart-title {
        font-size: .88rem; font-weight: 700; color: #003B6D; margin-bottom: 1rem;
    }
    .chart-box { position: relative; height: 280px; }

    .filter-panel {
        background: #fff; border-radius: .75rem; padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1.5rem;
    }
    .filter-panel .filter-title {
        font-size: .88rem; font-weight: 700; color: #003B6D; margin-bottom: 1rem;
    }

    .summary-card { background: #fff; border-radius: .75rem; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .summary-card .summary-header {
        background: #003B6D; color: #fff; padding: .85rem 1.25rem;
        display: flex; align-items: center; justify-content: space-between;
    }
    .summary-card .summary-header h6 { margin: 0; font-weight: 700; font-size: .9rem; }
</style>

<!-- ── Filter Panel ──────────────────────────────────────────────────────── -->
<div class="filter-panel">
    <div class="filter-title"><i class="fas fa-filter me-2"></i>ตัวกรองข้อมูล</div>
    <form method="GET" action="<?= BASE_URL ?>/dashboard">
        <div class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">ปีงบประมาณ</label>
                <select name="year[]" class="form-select form-select-sm" multiple>
                    <?php foreach ($filterYears ?? [] as $yr): ?>
                        <option value="<?= h($yr) ?>" <?= in_array($yr, $selectedYears ?? []) ? 'selected' : '' ?>>
                            <?= h($yr) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Ctrl/Cmd = หลายปี</div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">สาขาวิชา</label>
                <select name="field_of_study_id" class="form-select form-select-sm">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($filterFields ?? [] as $f): ?>
                        <option value="<?= (int)$f['id'] ?>"
                            <?= ((string)($selectedField ?? '') === (string)$f['id']) ? 'selected' : '' ?>>
                            <?= h($f['name_th']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1 d-block">ประเภทแหล่งทุน</label>
                <div class="d-flex gap-3 flex-wrap pt-1">
                    <?php foreach (['' => 'ทั้งหมด', 'internal' => 'ภายใน', 'external' => 'ภายนอก'] as $val => $lbl): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="funding_type"
                                   value="<?= h($val) ?>"
                                   <?= (($selectedFunding ?? '') === $val) ? 'checked' : '' ?>>
                            <label class="form-check-label small"><?= $lbl ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search me-1"></i>แสดงผล
                </button>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-outline-secondary">รีเซ็ต</a>
            </div>

            <!-- Month checkboxes -->
            <div class="col-12">
                <label class="form-label small fw-semibold mb-1 d-block">เดือน</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($thaiMonthNames as $mNum => $mName): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox"
                                   name="month[]" value="<?= $mNum ?>" id="m<?= $mNum ?>"
                                   <?= in_array($mNum, $selectedMonths ?? []) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="m<?= $mNum ?>"><?= $mName ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- ── KPI Cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-primary">
            <div class="kpi-bg-icon"><i class="fas fa-file-alt"></i></div>
            <div class="kpi-number"><?= number_format((int)($kpis['total_proposals'] ?? 0)) ?></div>
            <div class="kpi-label">โครงการทั้งหมด</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-success">
            <div class="kpi-bg-icon"><i class="fas fa-coins"></i></div>
            <div class="kpi-number"><?= number_format((float)($kpis['total_budget'] ?? 0) / 1000000, 2) ?></div>
            <div class="kpi-label">งบประมาณรวม</div>
            <div class="kpi-sub">ล้านบาท</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-warning">
            <div class="kpi-bg-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="kpi-number"><?= number_format((int)($kpis['reviewing'] ?? 0)) ?></div>
            <div class="kpi-label">รอพิจารณา</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-info">
            <div class="kpi-bg-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-number"><?= number_format((int)($kpis['approved'] ?? 0)) ?></div>
            <div class="kpi-label">อนุมัติแล้ว</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-orange">
            <div class="kpi-bg-icon"><i class="fas fa-spinner"></i></div>
            <div class="kpi-number"><?= number_format((int)($kpis['in_progress'] ?? 0)) ?></div>
            <div class="kpi-label">กำลังดำเนินการ</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card kpi-secondary">
            <div class="kpi-bg-icon"><i class="fas fa-flag-checkered"></i></div>
            <div class="kpi-number"><?= number_format((int)($kpis['closed'] ?? 0)) ?></div>
            <div class="kpi-label">ปิดแล้ว</div>
        </div>
    </div>
</div>

<!-- ── Charts Row 1: Bar + Doughnut ─────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-bar me-2"></i>โครงการแยกตามแหล่งทุน</div>
            <div class="chart-box"><canvas id="chartFunding"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-pie me-2"></i>ทุนภายใน vs ภายนอก</div>
            <div class="chart-box"><canvas id="chartType"></canvas></div>
        </div>
    </div>
</div>

<!-- ── Charts Row 2: Line ────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-line me-2"></i>แนวโน้มโครงการรายเดือน (12 เดือนล่าสุด)</div>
            <div class="chart-box"><canvas id="chartMonthly"></canvas></div>
        </div>
    </div>
</div>

<!-- ── Charts Row 3: Horiz Bar + Stacked Bar ────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-bar me-2"></i>งบประมาณแยกตามสาขาวิชา (ล้านบาท)</div>
            <div class="chart-box"><canvas id="chartField"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-layer-group me-2"></i>สถานะโครงการแยกตามปีงบประมาณ</div>
            <div class="chart-box"><canvas id="chartStatusYear"></canvas></div>
        </div>
    </div>
</div>

<!-- ── Summary Table ─────────────────────────────────────────────────────── -->
<div class="summary-card mb-4">
    <div class="summary-header">
        <h6><i class="fas fa-table me-2"></i>สรุปโครงการแยกตามแหล่งทุน</h6>
        <button onclick="window.print()" class="btn btn-sm btn-light">
            <i class="fas fa-print me-1"></i>พิมพ์
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>แหล่งทุน</th>
                    <th>ประเภท</th>
                    <th class="text-center">รอพิจารณา</th>
                    <th class="text-center">อนุมัติ</th>
                    <th class="text-center">ไม่อนุมัติ</th>
                    <th class="text-center">รวม</th>
                    <th class="text-end">งบรวม (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>
                <?php else: ?>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td class="fw-semibold small"><?= h($row['funding_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= ($row['type'] ?? '') === 'internal' ? 'bg-primary' : 'bg-success' ?> bg-opacity-75">
                                <?= ($row['type'] ?? '') === 'internal' ? 'ภายใน' : 'ภายนอก' ?>
                            </span>
                        </td>
                        <td class="text-center text-warning fw-semibold"><?= (int)($row['reviewing'] ?? 0) ?></td>
                        <td class="text-center text-success fw-semibold"><?= (int)($row['approved']  ?? 0) ?></td>
                        <td class="text-center text-danger"><?= (int)($row['rejected']  ?? 0) ?></td>
                        <td class="text-center fw-bold"><?= (int)($row['total'] ?? 0) ?></td>
                        <td class="text-end small">฿<?= number_format((float)($row['total_budget'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Chart.js Init ─────────────────────────────────────────────────────── -->
<script>
(function () {
    const PSU = ['#003B6D','#0066CC','#0099FF','#33BBFF','#66CCFF','#99DDFF','#CCF0FF'];
    Chart.defaults.font.family = "'Segoe UI','Noto Sans Thai',Arial,sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#495057';

    // Chart 1 – Bar: Proposals by Funding Source
    const fundingLabels = <?= $chartFundingLabels ?>;
    const fundingValues = <?= $chartFundingValues ?>;
    if (document.getElementById('chartFunding') && fundingLabels.length) {
        new Chart(document.getElementById('chartFunding'), {
            type: 'bar',
            data: {
                labels: fundingLabels,
                datasets: [{
                    label: 'จำนวนโครงการ',
                    data: fundingValues,
                    backgroundColor: fundingLabels.map((_, i) => PSU[i % PSU.length]),
                    borderRadius: 6, borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Chart 2 – Doughnut: Internal vs External
    const typeLabels = <?= $chartTypeLabels ?>;
    const typeValues = <?= $chartTypeValues ?>;
    if (document.getElementById('chartType')) {
        new Chart(document.getElementById('chartType'), {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{ data: typeValues, backgroundColor: ['#003B6D','#0099FF'], borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, boxWidth: 14 } },
                    tooltip: { callbacks: { label: ctx => {
                        const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const p = t > 0 ? Math.round(ctx.parsed / t * 100) : 0;
                        return ` ${ctx.label}: ${ctx.parsed} (${p}%)`;
                    }}}
                }
            }
        });
    }

    // Chart 3 – Line: Monthly Trend
    const monthLabels = <?= $chartMonthLabels ?>;
    const monthValues = <?= $chartMonthValues ?>;
    if (document.getElementById('chartMonthly')) {
        new Chart(document.getElementById('chartMonthly'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'จำนวนโครงการ', data: monthValues,
                    borderColor: '#0066CC', backgroundColor: 'rgba(0,102,204,0.08)',
                    pointBackgroundColor: '#003B6D', pointRadius: 5, pointHoverRadius: 7,
                    borderWidth: 2.5, tension: 0.35, fill: true,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Chart 4 – Horizontal Bar: Budget by Field
    const fieldLabels  = <?= $chartFieldLabels ?>;
    const fieldBudgets = <?= $chartFieldBudgets ?>;
    if (document.getElementById('chartField') && fieldLabels.length) {
        new Chart(document.getElementById('chartField'), {
            type: 'bar',
            data: {
                labels: fieldLabels,
                datasets: [{
                    label: 'งบประมาณ (ล้านบาท)', data: fieldBudgets,
                    backgroundColor: fieldLabels.map((_, i) => PSU[i % PSU.length]),
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ' ฿' + ctx.parsed.x.toLocaleString() + ' ล้านบาท' }}},
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { callback: v => '฿' + v + 'M' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // Chart 5 – Stacked Bar: Status by Year
    const stackLabels   = <?= $stackedLabels ?>;
    const stackDatasets = <?= $stackedDatasets ?>;
    // Assign colors to each dataset
    const stackColors = ['#003B6D','#0066CC','#10b981','#f59e0b','#0ea5e9','#dc2626'];
    stackDatasets.forEach((ds, i) => { ds.backgroundColor = stackColors[i % stackColors.length]; });
    if (document.getElementById('chartStatusYear') && stackLabels.length) {
        new Chart(document.getElementById('chartStatusYear'), {
            type: 'bar',
            data: { labels: stackLabels, datasets: stackDatasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } }
                }
            }
        });
    }
})();
</script>
