<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['doctor_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$doctor_id = intval($_GET['doctor_id']);
$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['name'];

// Verify doctor exists
$stmt = $conn->prepare("SELECT name, specialty FROM users WHERE id = ? AND role = 'doctor'");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

if (!$doctor) {
    echo "Doctor not found.";
    exit;
}

$room_name = "HealthTech_Consultation_Doc_" . $doctor_id . "_Pat_" . $patient_id;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Consultation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="style.css" rel="stylesheet">
    <script src='https://meet.jit.si/external_api.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        #jitsi-container {
            width: 100%;
            height: 700px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                <span>HealthTech</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link btn btn-light btn-sm text-primary px-3" href="register.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5 mb-5">
        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h2>Online Consultation Room</h2>
                <p class="text-muted mb-0">Consulting with <strong><?php echo htmlspecialchars($doctor['name']); ?></strong> (<?php echo htmlspecialchars($doctor['specialty']); ?>)</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?php echo $_SESSION['role'] === 'doctor' ? 'doctor_dashboard.php' : 'patient_dashboard.php'; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div id="jitsi-container"></div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h5>Stay Connected</h5>
                    <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
                    <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
                    <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const domain = 'meet.jit.si';
            let finalRoomName = '<?php echo $room_name; ?>';
            <?php if ($_SESSION['role'] === 'doctor' && isset($_GET['patient_id'])): ?>
            finalRoomName = 'HealthTech_Consultation_Doc_<?php echo $doctor_id; ?>_Pat_<?php echo intval($_GET['patient_id']); ?>';
            <?php endif; ?>

            const options = {
                roomName: finalRoomName,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsi-container'),
                userInfo: {
                    displayName: '<?php echo htmlspecialchars($patient_name); ?>'
                },
                configOverwrite: { 
                    startWithAudioMuted: false, 
                    startWithVideoMuted: false,
                    prejoinPageEnabled: false
                },
                interfaceConfigOverwrite: {
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false,
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                        'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                        'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                        'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                        'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone', 'e2ee'
                    ]
                }
            };
            const api = new JitsiMeetExternalAPI(domain, options);
            
            // Open the Chat panel automatically as soon as the meeting starts
            api.addEventListener('videoConferenceJoined', () => {
                api.executeCommand('toggleChat');
            });

            api.addEventListener('videoConferenceLeft', () => {
                window.location.href = '<?php echo $_SESSION['role'] === 'doctor' ? 'doctor_dashboard.php' : 'patient_dashboard.php'; ?>';
            });
        });
    </script>
    <!-- Floating Show Report Button for Patients -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
        <button class="btn btn-primary" style="position: fixed; bottom: 30px; left: 30px; z-index: 9999; border-radius: 50px; padding: 10px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);" data-bs-toggle="modal" data-bs-target="#reportModal">
            <i class="bi bi-file-earmark-medical"></i> Show Report
        </button>

        <!-- Report Modal -->
        <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Upload Medical Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="standaloneReportForm">
                    <div class="mb-3">
                        <label class="form-label">Select PDF or Image</label>
                        <input type="file" class="form-control" name="report_file" required accept=".pdf,image/*">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="uploadBtn">Upload Report</button>
                    <p class="text-muted mt-2 small text-center">Your report page link will be generated after upload. You can share it in the video chat.</p>
                </form>
                <div id="uploadResult" class="mt-3"></div>
              </div>
            </div>
          </div>
        </div>
        
        <script>
            document.getElementById('standaloneReportForm').addEventListener('submit', function(e){
                e.preventDefault();
                const btn = document.getElementById('uploadBtn');
                btn.innerHTML = 'Uploading...';
                btn.disabled = true;
                
                const fd = new FormData(this);
                fd.append('doctor_id', '<?php echo $doctor_id; ?>');
                <?php if ($_SESSION['role'] === 'patient'): ?>
                fd.append('patient_id', '<?php echo $patient_id; ?>');
                <?php endif; ?>

                fetch('upload.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    btn.innerHTML = 'Upload Report';
                    btn.disabled = false;
                    if(data.status === 'success') {
                        document.getElementById('uploadResult').innerHTML = 
                            '<div class="alert alert-success mt-2"><strong>Success!</strong><br><a href="'+data.file_url+'" target="_blank" class="alert-link">Open My Report</a><br><br><small><strong>Tip for Doctor:</strong> You can copy the link above and paste it in the Jitsi chat box for the doctor, or just open it and share your screen!</small></div>';
                    } else {
                        document.getElementById('uploadResult').innerHTML = '<div class="alert alert-danger">'+data.message+'</div>';
                    }
                }).catch(err => {
                    btn.innerHTML = 'Upload Report';
                    btn.disabled = false;
                    document.getElementById('uploadResult').innerHTML = '<div class="alert alert-danger">Error uploading.</div>';
                });
            });
        </script>
    <?php endif; ?>
    <!-- Floating Prescribe Medicine Button for Doctors -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'doctor'): ?>
        <button class="btn btn-success" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; border-radius: 50px; padding: 10px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);" data-bs-toggle="modal" data-bs-target="#prescriptionModal">
            <i class="bi bi-pencil-square"></i> Prescribe Medicine
        </button>

        <!-- Prescription Modal -->
        <div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="prescriptionModalLabel"><i class="bi bi-file-medical"></i> Write Prescription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="printPrescriptionArea">
                <div class="text-center mb-4">
                    <h3 class="text-primary">HealthTech E-Prescription</h3>
                    <p class="mb-0"><strong>Doctor:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <hr>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Rx (Medicines, Dosages, & Advice):</label>
                    <textarea class="form-control d-print-none" id="rxInput" rows="6" placeholder="Example: Paracetamol 500mg 1+1+1 for 7 days..."></textarea>
                    <div class="d-none d-print-block mt-3" style="white-space: pre-wrap;" id="rxPrintView"></div>
                </div>
              </div>
              <div class="modal-footer d-print-none">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="generateRxBtn"><i class="bi bi-printer"></i> Generate & Print/Save as PDF</button>
              </div>
            </div>
          </div>
        </div>

        <script>
            document.getElementById('generateRxBtn').addEventListener('click', function() {
                const rxValue = document.getElementById('rxInput').value;
                if(!rxValue.trim()) {
                    alert('Please write medicine before generating prescription!');
                    return;
                }
                
                // Save prescription to server silently for patient
                const fd = new FormData();
                fd.append('doctor_id', '<?php echo $doctor_id; ?>');
                fd.append('patient_id', '<?php echo $patient_id; ?>');
                fd.append('rx', rxValue);
                fetch('save_rx.php', { method: 'POST', body: fd });
                
                // Sync to print view
                document.getElementById('rxPrintView').innerText = rxValue;
                
                // Print the specific area
                const printContents = document.getElementById('printPrescriptionArea').innerHTML;
                const originalContents = document.body.innerHTML;
                
                document.body.innerHTML = printContents;
                window.print();
                
                // Restore page (will reload to restore jitsi state cleanly)
                window.location.reload();
            });
        </script>
    <?php endif; ?>

    <!-- Floating Show Prescription Button for Patients -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
        <button class="btn btn-success" style="position: fixed; bottom: 80px; left: 30px; z-index: 9999; border-radius: 50px; padding: 10px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);" data-bs-toggle="modal" data-bs-target="#showRxModal">
            <i class="bi bi-file-medical"></i> Show Prescription
        </button>

        <!-- Provide Prescription Viewer Modal -->
        <div class="modal fade" id="showRxModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-medical"></i> Doctor's Prescription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body bg-light">
                <div id="rxPatientPrintArea" class="p-5" style="background: white; border: 2px solid #28a745; border-radius: 12px; min-height: 400px; box-shadow: 0 0 15px rgba(0,0,0,0.05);">
                    <div class="text-center mb-4 pb-3 border-bottom border-success">
                        <h2 class="text-success mb-3 fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i>HealthTech E-Prescription</h2>
                        <h4 class="mb-1 text-dark"><strong>Dr. <?php echo htmlspecialchars($doctor['name']); ?></strong></h4>
                        <p class="mb-0 text-muted fs-5"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                    </div>
                    <div class="pt-2">
                        <h4 class="text-success fw-bold mb-3"><i class="bi bi-capsule me-2"></i>Rx</h4>
                        <div id="rxPatientText" style="white-space: pre-wrap; font-size:1.15rem; min-height: 150px; color: #333; line-height: 1.8;">
                            Loading prescription...
                        </div>
                    </div>
                </div>
              </div>
              <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="downloadRxPicBtn"><i class="bi bi-download me-1"></i> Download as Picture</button>
              </div>
            </div>
          </div>
        </div>
        
        <script>
            // Fetch prescription from file when patient opens modal
            const showRxModal = document.getElementById('showRxModal');
            if(showRxModal) {
                showRxModal.addEventListener('show.bs.modal', function() {
                    const textContainer = document.getElementById('rxPatientText');
                    textContainer.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split"></i> Loading prescription...</span>';
                    
                    fetch('uploads/rx_<?php echo $doctor_id; ?>_<?php echo $patient_id; ?>.txt?time=' + new Date().getTime())
                    .then(response => {
                        if (!response.ok) throw new Error('Not found');
                        return response.text();
                    })
                    .then(text => {
                        textContainer.innerText = text;
                    })
                    .catch(e => {
                        textContainer.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i> Doctor has not generated the prescription yet.</span>';
                    });
                });
            }
            
            // Generate picture using html2canvas
            document.getElementById('downloadRxPicBtn').addEventListener('click', function() {
                const targetArea = document.getElementById('rxPatientPrintArea');
                const btn = this;
                btn.innerHTML = '<i class="bi bi-hourglass me-1"></i> Downloading...';
                
                html2canvas(targetArea, {scale: 2, useCORS: true}).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'HealthTech_Prescription.png';
                    link.href = canvas.toDataURL("image/png");
                    link.click();
                    btn.innerHTML = '<i class="bi bi-download me-1"></i> Download as Picture';
                }).catch(err => {
                    alert('Could not generate picture.');
                    btn.innerHTML = '<i class="bi bi-download me-1"></i> Download as Picture';
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
