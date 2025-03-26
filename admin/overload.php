<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


<div class="tabular--wrapper">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="successMessage">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" id="errorMessage">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>


    <div class="add">
        <div class="filter">
            <form method="GET" action="" class="d-flex align-items-center">
            <input type="text" name="search_user" placeholder="Search user..." 
                    value="<?php echo isset($_GET['search_user']) ? $_GET['search_user'] : ''; ?>" 
                    style="width: 200px; margin-right: 10px; height: 43px;">

            <select name="academic_year" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                <option value="" selected>Select Academic Year</option>
                <?php
                $academicYearQuery = "SELECT * FROM academic_years";
                $academicYearResult = $con->query($academicYearQuery);
                while ($academicYear = $academicYearResult->fetch_assoc()):
                ?>
                <option value="<?php echo $academicYear['academic_year_id']; ?>" 
                    <?php echo (isset($_GET['academic_year']) && $_GET['academic_year'] == $academicYear['academic_year_id']) ? 'selected' : ''; ?>>
                    <?php echo $academicYear['academic_year']; ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="dept_filter" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 190px;">
                <option value="" disabled selected>Select Department</option>
                <option value="ALL" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == 'ALL') echo 'selected'; ?>>All</option>
                <option value="1" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '1') echo 'selected'; ?>>IT</option>
                <option value="3" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '3') echo 'selected'; ?>>CS</option>
                <option value="4" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '4') echo 'selected'; ?>>DS</option>
                <option value="2" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '2') echo 'selected'; ?>>TCM</option>
            </select>
            </form>
            
        </div>
        </div>

        <div class="table-container">
        <?php
            $search_user = isset($_GET['search_user']) ? $_GET['search_user'] : '';
            $academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
            $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
            $deptFilter = $_GET['dept_filter'] ?? null;
            $department_id = $_GET['dept_id'] ?? '';

            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
            $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

            $query = "SELECT d.id, d.userId, d.academic_year_id, d.semester_id, 
                    d.week1, d.week2, d.week3, d.week4, d.week5, d.overall_total, 
                    d.fileName, d.month_year, e.department, dept.dept_id, dept.departmentName,
                    e.firstName, e.middleName, e.lastName, e.employeeId,
                    a.academic_year, s.semester_name, 
                    COALESCE(itl.totalOverload, 0) AS totalOverload,
                    itl.designated,
                    d.week1_overload, d.week2_overload, d.week3_overload, d.week4_overload
                FROM dtr_extracted_data d
                JOIN employee e ON d.userId = e.userId
                JOIN department dept ON e.department = dept.dept_id
                JOIN academic_years a ON d.academic_year_id = a.academic_year_id
                JOIN semesters s ON d.semester_id = s.semester_id
                LEFT JOIN itl_extracted_data itl ON d.userId = itl.userId
                WHERE 1=1";

            if (!empty($search_user)) {
                $search_user = $con->real_escape_string($search_user);
                $query .= " AND (e.firstName LIKE '%$search_user%' 
                                OR e.middleName LIKE '%$search_user%' 
                                OR e.lastName LIKE '%$search_user%' 
                                OR e.employeeId LIKE '%$search_user%')";
            }
            if ($deptFilter && $deptFilter !== 'ALL') {
                $whereClauses[] = "e.department = '$deptFilter'";
            }
            
            if (!empty($whereClauses)) {
                $query .= " AND " . implode(' AND ', $whereClauses);
            }

            if (!empty($academic_year)) {
                $query .= " AND d.academic_year_id = $academic_year";
            }

            if (!empty($semester)) {
                $query .= " AND itl.semester_id = $semester";
            }

            $query .= " ORDER BY e.firstName $order, e.middleName $order, e.lastName $order";
            $result = $con->query($query);

        
            if (!$result) {
                die("Error fetching data: " . $con->error);
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $key = $row['userId'] . '_' . $row['academic_year_id'];
                $month = date('F', strtotime($row['month_year']));

                // ARRAY
                if (!isset($data[$key])) {
                    $data[$key] = [
                        'userId' => $row['userId'],
                        'name' => $row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName'],
                        'department' => $row['departmentName'],
                        'designation' => $row['designated'],
                        'academic_year' => $row['academic_year'],
                        'months' => []
                    ];
                }

                $totalCredits = 0;
                $weekOverloads = 0;
                foreach (['week1_overload', 'week2_overload', 'week3_overload', 'week4_overload'] as $week) {
                    if (is_numeric($row[$week])) {
                        $weekOverloads += $row[$week];
                        if ($row[$week] > 12) {
                            $totalCredits += $row[$week] - 12;
                        }
                    }
                }

                $weekOverloads = max($weekOverloads - $totalCredits, 0);
                $data[$key]['months'][$month] = [
                    'credits' => $totalCredits > 0 ? htmlspecialchars($totalCredits) : '',
                    'overload' => $weekOverloads > 0 ? htmlspecialchars($weekOverloads) : ''
                ];
            }
            
            ?>

            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">No.</th>
                        <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Faculty</th>
                        <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Department</th>
                        <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Designation</th>
                        <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap" >Academic Year</th>
                        <?php foreach ([
                            'January', 'February', 'March', 
                            'April', 'May', 'June', 'July',
                            'August', 'September', 'October', 'November', 
                            'December'] as $month): ?>
                            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap"><?php echo $month; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td style="font-size: 12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td style="font-size: 12px;"><?php echo htmlspecialchars($row['department']); ?></td>
                                <td style="font-size: 12px;"><?php echo htmlspecialchars($row['designation']); ?></td>
                                <td style="font-size: 12px;"><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                <?php foreach ([
                                    'January', 'February', 'March', 
                                    'April', 'May', 'June', 'July',
                                    'August', 'September', 'October', 'November', 
                                    'December'] as $month): ?>
                                    <td style="white-space: nowrap; font-size: 12px; ">
                                        <?php if (isset($row['months'][$month])): ?>
                                            <strong>Credits: </strong><?php echo $row['months'][$month]['credits'] ? $row['months'][$month]['credits']: 0 ; ?><br>
                                            <strong>Overload: </strong> <?php echo $row['months'][$month]['overload'] ? $row['months'][$month]['overload']: 0 ; ?>
                                        <?php else: ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="18" style="text-align:center;">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

<?php
include('./includes/footer.php');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
