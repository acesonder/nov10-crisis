<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Substance Use Assessment';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $assessment_data = [
        'currently_using' => $_POST['currently_using'] ?? '',
        'substances_used' => $_POST['substances_used'] ?? [],
        'frequency' => $_POST['frequency'] ?? '',
        'duration' => $_POST['duration'] ?? '',
        'tried_quitting' => $_POST['tried_quitting'] ?? '',
        'previous_treatment' => $_POST['previous_treatment'] ?? '',
        'support_system' => $_POST['support_system'] ?? '',
        'living_situation' => $_POST['living_situation'] ?? '',
        'employment' => $_POST['employment'] ?? '',
        'mental_health' => $_POST['mental_health'] ?? '',
        'medical_issues' => $_POST['medical_issues'] ?? '',
        'legal_issues' => $_POST['legal_issues'] ?? '',
        'motivation_level' => $_POST['motivation_level'] ?? '',
        'immediate_needs' => $_POST['immediate_needs'] ?? [],
        'goals' => $_POST['goals'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    // Calculate risk level based on responses
    $risk_level = 'low';
    if ($_POST['currently_using'] === 'yes' && $_POST['frequency'] === 'daily') {
        $risk_level = 'high';
    } elseif ($_POST['currently_using'] === 'yes') {
        $risk_level = 'medium';
    }
    
    $status = isset($_POST['save_draft']) ? 'draft' : 'completed';
    $completed_at = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    
    $json_data = json_encode($assessment_data);
    
    $stmt = $conn->prepare("INSERT INTO assessments (client_id, assessment_type, assessment_data, risk_level, status, completed_at) VALUES (?, 'substance_use', ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $json_data, $risk_level, $status, $completed_at);
    
    if ($stmt->execute()) {
        $assessment_id = $stmt->insert_id;
        $stmt->close();
        
        log_activity($conn, $user_id, 'assessment_created', 'assessment', $assessment_id);
        
        // Create notification for staff
        create_notification($conn, 1, 'assessment', 'New Assessment Completed', 
            $_SESSION['full_name'] . ' completed a substance use assessment', 
            '/nov10-crisis/pages/staff/assessment-view.php?id=' . $assessment_id);
        
        if ($status === 'completed') {
            // Auto-match with service providers
            $providers_stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE service_type = 'substance_abuse' AND status = 'active' AND available_slots > 0 LIMIT 3");
            $providers_stmt->execute();
            $providers = $providers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $providers_stmt->close();
            
            foreach ($providers as $provider) {
                $referral_stmt = $conn->prepare("INSERT INTO referrals (client_id, provider_id, assessment_id, referral_reason, priority, status) VALUES (?, ?, ?, 'Auto-matched based on substance use assessment', ?, 'pending')");
                $referral_priority = $risk_level === 'high' ? 'urgent' : 'medium';
                $referral_stmt->bind_param("iiis", $user_id, $provider['provider_id'], $assessment_id, $referral_priority);
                $referral_stmt->execute();
                $referral_stmt->close();
            }
        }
        
        header("Location: /nov10-crisis/pages/client/assessments.php?success=1");
        exit;
    } else {
        $error = 'Failed to save assessment. Please try again.';
    }
}

include '../../includes/header.php';
?>

<style>
    .assessment-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .section {
        background: var(--bg-primary);
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
    }
    
    .section-title {
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--border-color);
    }
    
    .optional-tag {
        font-size: 0.875rem;
        color: var(--success-color);
        font-weight: normal;
    }
</style>

<div class="assessment-container" style="padding: 2rem 20px;">
    <div class="mb-4 text-center">
        <h1>Substance Use Assessment 💊</h1>
        <p style="color: var(--text-secondary);">All questions are optional. Answer only what you're comfortable sharing.</p>
        <p style="color: var(--info-color);"><strong>Your responses are confidential and help us connect you with appropriate services.</strong></p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="assessment-form">
        <!-- Current Use Section -->
        <div class="section">
            <h3 class="section-title">Current Substance Use <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">Are you currently using any substances?</label>
                <select name="currently_using" class="form-control" id="currently-using">
                    <option value="">Prefer not to answer</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                    <option value="past">Not currently, but in the past</option>
                </select>
            </div>
            
            <div class="form-group" id="substances-group" style="display: none;">
                <label class="form-label">Which substances? (Check all that apply)</label>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="alcohol" class="form-check-input">
                    <label class="form-check-label">Alcohol</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="marijuana" class="form-check-input">
                    <label class="form-check-label">Marijuana/Cannabis</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="opioids" class="form-check-input">
                    <label class="form-check-label">Opioids (prescription painkillers, heroin, fentanyl)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="stimulants" class="form-check-input">
                    <label class="form-check-label">Stimulants (cocaine, meth, crack)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="benzodiazepines" class="form-check-input">
                    <label class="form-check-label">Benzodiazepines (Xanax, Valium)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="substances_used[]" value="other" class="form-check-input">
                    <label class="form-check-label">Other</label>
                </div>
            </div>
            
            <div class="form-group" id="frequency-group" style="display: none;">
                <label class="form-label">How often do you use?</label>
                <select name="frequency" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="daily">Daily</option>
                    <option value="several_times_week">Several times a week</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="occasionally">Occasionally</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">How long have you been using substances?</label>
                <select name="duration" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="less_than_6_months">Less than 6 months</option>
                    <option value="6_months_to_1_year">6 months to 1 year</option>
                    <option value="1_to_3_years">1 to 3 years</option>
                    <option value="3_to_5_years">3 to 5 years</option>
                    <option value="more_than_5_years">More than 5 years</option>
                </select>
            </div>
        </div>
        
        <!-- Treatment History -->
        <div class="section">
            <h3 class="section-title">Treatment History <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">Have you tried to quit or reduce use before?</label>
                <select name="tried_quitting" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="yes_multiple">Yes, multiple times</option>
                    <option value="yes_once">Yes, once</option>
                    <option value="no">No</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Have you received treatment or counseling before?</label>
                <select name="previous_treatment" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="yes_completed">Yes, completed program</option>
                    <option value="yes_incomplete">Yes, but didn't complete</option>
                    <option value="currently_in">Currently in treatment</option>
                    <option value="no">No, never</option>
                </select>
            </div>
        </div>
        
        <!-- Support & Circumstances -->
        <div class="section">
            <h3 class="section-title">Current Situation <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">Do you have a support system (family, friends, etc.)?</label>
                <select name="support_system" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="strong">Yes, strong support</option>
                    <option value="some">Some support</option>
                    <option value="limited">Limited support</option>
                    <option value="none">No support system</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Current living situation</label>
                <select name="living_situation" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="stable_housing">Stable housing</option>
                    <option value="temporary">Temporary housing</option>
                    <option value="unstable">Unstable housing</option>
                    <option value="homeless">Currently homeless</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Employment status</label>
                <select name="employment" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="employed_full">Employed full-time</option>
                    <option value="employed_part">Employed part-time</option>
                    <option value="unemployed">Unemployed</option>
                    <option value="disabled">Disabled</option>
                    <option value="student">Student</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Do you have any mental health concerns?</label>
                <textarea name="mental_health" class="form-control" rows="3" placeholder="Optional - Depression, anxiety, trauma, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Any medical issues we should know about?</label>
                <textarea name="medical_issues" class="form-control" rows="3" placeholder="Optional"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Any legal issues related to substance use?</label>
                <textarea name="legal_issues" class="form-control" rows="2" placeholder="Optional"></textarea>
            </div>
        </div>
        
        <!-- Motivation & Goals -->
        <div class="section">
            <h3 class="section-title">Your Goals <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">How motivated are you to make a change?</label>
                <select name="motivation_level" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="very_motivated">Very motivated</option>
                    <option value="somewhat_motivated">Somewhat motivated</option>
                    <option value="unsure">Unsure</option>
                    <option value="not_ready">Not ready yet</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">What are your immediate needs? (Check all that apply)</label>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="detox" class="form-check-input">
                    <label class="form-check-label">Detox services</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="counseling" class="form-check-input">
                    <label class="form-check-label">Counseling/therapy</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="medication" class="form-check-input">
                    <label class="form-check-label">Medication-assisted treatment</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="housing" class="form-check-input">
                    <label class="form-check-label">Housing support</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="support_group" class="form-check-input">
                    <label class="form-check-label">Support group (AA, NA, etc.)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="job_training" class="form-check-input">
                    <label class="form-check-label">Job training/employment help</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">What are your recovery goals?</label>
                <textarea name="goals" class="form-control" rows="4" placeholder="Optional - What would you like to achieve?"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Anything else you'd like us to know?</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Optional"></textarea>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" name="save_draft" class="btn btn-secondary btn-lg">Save as Draft</button>
            <button type="submit" name="submit" class="btn btn-primary btn-lg">Submit Assessment</button>
            <a href="/nov10-crisis/pages/client/assessments.php" class="btn btn-light btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentlyUsing = document.getElementById('currently-using');
    const substancesGroup = document.getElementById('substances-group');
    const frequencyGroup = document.getElementById('frequency-group');
    
    currentlyUsing.addEventListener('change', function() {
        if (this.value === 'yes' || this.value === 'past') {
            substancesGroup.style.display = 'block';
        } else {
            substancesGroup.style.display = 'none';
        }
        
        if (this.value === 'yes') {
            frequencyGroup.style.display = 'block';
        } else {
            frequencyGroup.style.display = 'none';
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
