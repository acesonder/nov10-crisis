<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Housing/Homelessness Assessment';
$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_data = [
        'current_housing_status' => $_POST['current_housing_status'] ?? '',
        'duration_homeless' => $_POST['duration_homeless'] ?? '',
        'reason_for_homelessness' => $_POST['reason_for_homelessness'] ?? [],
        'previous_housing' => $_POST['previous_housing'] ?? '',
        'safe_tonight' => $_POST['safe_tonight'] ?? '',
        'sleeping_location' => $_POST['sleeping_location'] ?? '',
        'income_source' => $_POST['income_source'] ?? '',
        'monthly_income' => $_POST['monthly_income'] ?? '',
        'employment_status' => $_POST['employment_status'] ?? '',
        'eviction_history' => $_POST['eviction_history'] ?? '',
        'criminal_record' => $_POST['criminal_record'] ?? '',
        'health_issues' => $_POST['health_issues'] ?? '',
        'mental_health' => $_POST['mental_health'] ?? '',
        'substance_use' => $_POST['substance_use'] ?? '',
        'disabilities' => $_POST['disabilities'] ?? '',
        'dependents' => $_POST['dependents'] ?? '',
        'veteran_status' => $_POST['veteran_status'] ?? '',
        'housing_preference' => $_POST['housing_preference'] ?? [],
        'immediate_needs' => $_POST['immediate_needs'] ?? [],
        'barriers_to_housing' => $_POST['barriers_to_housing'] ?? '',
        'goals' => $_POST['goals'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    // Calculate risk level
    $risk_level = 'low';
    if ($_POST['current_housing_status'] === 'homeless_street' || $_POST['safe_tonight'] === 'no') {
        $risk_level = 'critical';
    } elseif ($_POST['current_housing_status'] === 'homeless_shelter' || $_POST['current_housing_status'] === 'temporary') {
        $risk_level = 'high';
    } elseif ($_POST['current_housing_status'] === 'at_risk') {
        $risk_level = 'medium';
    }
    
    $status = isset($_POST['save_draft']) ? 'draft' : 'completed';
    $completed_at = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    $json_data = json_encode($assessment_data);
    
    $stmt = $conn->prepare("INSERT INTO assessments (client_id, assessment_type, assessment_data, risk_level, status, completed_at) VALUES (?, 'homelessness', ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $json_data, $risk_level, $status, $completed_at);
    
    if ($stmt->execute()) {
        $assessment_id = $stmt->insert_id;
        $stmt->close();
        
        log_activity($conn, $user_id, 'assessment_created', 'assessment', $assessment_id);
        create_notification($conn, 1, 'assessment', 'New Housing Assessment', 
            $_SESSION['full_name'] . ' completed a housing assessment', 
            '/nov10-crisis/pages/staff/assessment-view.php?id=' . $assessment_id);
        
        if ($status === 'completed') {
            // Auto-match with housing providers
            $providers_stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE service_type IN ('emergency_housing', 'transitional_housing') AND status = 'active' AND available_slots > 0 LIMIT 3");
            $providers_stmt->execute();
            $providers = $providers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $providers_stmt->close();
            
            foreach ($providers as $provider) {
                $referral_stmt = $conn->prepare("INSERT INTO referrals (client_id, provider_id, assessment_id, referral_reason, priority, status) VALUES (?, ?, ?, 'Auto-matched based on housing needs', ?, 'pending')");
                $referral_priority = $risk_level === 'critical' ? 'urgent' : 'high';
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
    
    .urgent-message {
        background: #fff3cd;
        border-left: 4px solid var(--warning-color);
        padding: 1rem;
        margin-bottom: 2rem;
        border-radius: var(--border-radius);
    }
</style>

<div class="assessment-container" style="padding: 2rem 20px;">
    <div class="mb-4 text-center">
        <h1>Housing/Homelessness Assessment 🏠</h1>
        <p style="color: var(--text-secondary);">All questions are optional. Answer only what you're comfortable sharing.</p>
        <p style="color: var(--info-color);"><strong>Your responses help us connect you with appropriate housing resources.</strong></p>
    </div>
    
    <div class="urgent-message">
        <strong>⚠️ If you need immediate shelter tonight:</strong><br>
        Call 211 or visit your nearest emergency shelter. We'll help you find resources quickly.
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Current Housing Status -->
        <div class="section">
            <h3 class="section-title">Current Housing Situation <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">What is your current housing status?</label>
                <select name="current_housing_status" class="form-control" id="housing-status">
                    <option value="">Prefer not to answer</option>
                    <option value="homeless_street">Homeless - Street/outdoors</option>
                    <option value="homeless_shelter">Homeless - Staying in shelter</option>
                    <option value="homeless_vehicle">Homeless - Living in vehicle</option>
                    <option value="temporary">Temporary housing (friend/family)</option>
                    <option value="at_risk">Have housing but at risk of losing it</option>
                    <option value="stable">Stable housing</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">How long have you been without stable housing?</label>
                <select name="duration_homeless" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="less_than_week">Less than a week</option>
                    <option value="1_to_4_weeks">1 to 4 weeks</option>
                    <option value="1_to_6_months">1 to 6 months</option>
                    <option value="6_months_to_1_year">6 months to 1 year</option>
                    <option value="1_to_3_years">1 to 3 years</option>
                    <option value="more_than_3_years">More than 3 years</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Do you have a safe place to sleep tonight?</label>
                <select name="safe_tonight" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                    <option value="unsure">Unsure</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Where are you currently sleeping?</label>
                <input type="text" name="sleeping_location" class="form-control" placeholder="Optional - General location, not exact address">
            </div>
            
            <div class="form-group">
                <label class="form-label">What led to your housing situation? (Check all that apply)</label>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="job_loss" class="form-check-input">
                    <label class="form-check-label">Job loss/unemployment</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="eviction" class="form-check-input">
                    <label class="form-check-label">Eviction</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="relationship_breakdown" class="form-check-input">
                    <label class="form-check-label">Relationship breakdown/domestic violence</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="mental_health" class="form-check-input">
                    <label class="form-check-label">Mental health issues</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="substance_use" class="form-check-input">
                    <label class="form-check-label">Substance use</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="medical" class="form-check-input">
                    <label class="form-check-label">Medical issues/disability</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="reason_for_homelessness[]" value="affordability" class="form-check-input">
                    <label class="form-check-label">Housing too expensive</label>
                </div>
            </div>
        </div>
        
        <!-- Financial Situation -->
        <div class="section">
            <h3 class="section-title">Financial Situation <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">Current employment status</label>
                <select name="employment_status" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="employed_full">Employed full-time</option>
                    <option value="employed_part">Employed part-time</option>
                    <option value="unemployed">Unemployed</option>
                    <option value="disabled">Disabled/unable to work</option>
                    <option value="retired">Retired</option>
                    <option value="student">Student</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Source of income (if any)</label>
                <select name="income_source" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="employment">Employment</option>
                    <option value="benefits">Government benefits (SSI, SSDI, etc.)</option>
                    <option value="unemployment">Unemployment benefits</option>
                    <option value="family">Family/friends support</option>
                    <option value="none">No income</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Approximate monthly income</label>
                <select name="monthly_income" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="0">$0</option>
                    <option value="1_500">$1 - $500</option>
                    <option value="501_1000">$501 - $1,000</option>
                    <option value="1001_1500">$1,001 - $1,500</option>
                    <option value="1501_2000">$1,501 - $2,000</option>
                    <option value="2000_plus">$2,000+</option>
                </select>
            </div>
        </div>
        
        <!-- Background & Barriers -->
        <div class="section">
            <h3 class="section-title">Background Information <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">Have you been evicted before?</label>
                <select name="eviction_history" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="no">No</option>
                    <option value="yes_once">Yes, once</option>
                    <option value="yes_multiple">Yes, multiple times</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Do you have a criminal record?</label>
                <select name="criminal_record" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Are you a veteran?</label>
                <select name="veteran_status" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Do you have dependents (children, elderly parents)?</label>
                <select name="dependents" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="no">No</option>
                    <option value="children">Yes, children</option>
                    <option value="adults">Yes, adult dependents</option>
                    <option value="both">Yes, both</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Do you have any disabilities?</label>
                <input type="text" name="disabilities" class="form-control" placeholder="Optional">
            </div>
            
            <div class="form-group">
                <label class="form-label">Health issues that affect housing?</label>
                <textarea name="health_issues" class="form-control" rows="2" placeholder="Optional"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Mental health concerns?</label>
                <textarea name="mental_health" class="form-control" rows="2" placeholder="Optional"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Substance use affecting housing?</label>
                <select name="substance_use" class="form-control">
                    <option value="">Prefer not to answer</option>
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                    <option value="in_recovery">In recovery</option>
                </select>
            </div>
        </div>
        
        <!-- Housing Needs & Preferences -->
        <div class="section">
            <h3 class="section-title">Housing Preferences <span class="optional-tag">(Optional)</span></h3>
            
            <div class="form-group">
                <label class="form-label">What type of housing are you looking for? (Check all that apply)</label>
                <div class="form-check">
                    <input type="checkbox" name="housing_preference[]" value="emergency_shelter" class="form-check-input">
                    <label class="form-check-label">Emergency shelter (immediate)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="housing_preference[]" value="transitional" class="form-check-input">
                    <label class="form-check-label">Transitional housing</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="housing_preference[]" value="permanent_supportive" class="form-check-input">
                    <label class="form-check-label">Permanent supportive housing</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="housing_preference[]" value="affordable" class="form-check-input">
                    <label class="form-check-label">Affordable permanent housing</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="housing_preference[]" value="rapid_rehousing" class="form-check-input">
                    <label class="form-check-label">Rapid re-housing assistance</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">What do you need most right now? (Check all that apply)</label>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="shelter_tonight" class="form-check-input">
                    <label class="form-check-label">Shelter for tonight</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="food" class="form-check-input">
                    <label class="form-check-label">Food</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="clothing" class="form-check-input">
                    <label class="form-check-label">Clothing</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="shower" class="form-check-input">
                    <label class="form-check-label">Shower facilities</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="storage" class="form-check-input">
                    <label class="form-check-label">Storage for belongings</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="case_management" class="form-check-input">
                    <label class="form-check-label">Case management</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="immediate_needs[]" value="employment_help" class="form-check-input">
                    <label class="form-check-label">Employment help</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">What barriers do you face in getting housing?</label>
                <textarea name="barriers_to_housing" class="form-control" rows="3" placeholder="Optional - Bad credit, no ID, criminal record, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">What are your housing goals?</label>
                <textarea name="goals" class="form-control" rows="3" placeholder="Optional - What would stable housing look like for you?"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Anything else we should know?</label>
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

<?php include '../../includes/footer.php'; ?>
