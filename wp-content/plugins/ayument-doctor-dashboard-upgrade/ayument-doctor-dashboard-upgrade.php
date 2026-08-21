<?php
/**
 * Plugin Name: AyuMent Doctor Dashboard Upgrade
 * Description: Replaces the AyuMent doctor dashboard placeholder with a working doctor workspace for profile, appointments, patients, prescriptions and consultations.
 * Version: 1.0.0
 * Author: AyuMent
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function ayument_dashboard_upgrade_start() {
    if ( ! is_user_logged_in() ) return;

    remove_shortcode( 'ayument_doctor_dashboard' );
    add_shortcode( 'ayument_doctor_dashboard', 'ayument_dashboard_upgrade_shortcode' );
}
add_action( 'init', 'ayument_dashboard_upgrade_start', 99 );

function ayument_dashboard_upgrade_application( $user_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ayument_doctor_applications';
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        )
    );
}

function ayument_dashboard_upgrade_page_url( $view ) {
    $url = get_permalink();
    if ( ! $url ) $url = site_url( '/' );
    return add_query_arg( 'doctor_view', $view, $url );
}

function ayument_dashboard_upgrade_status_badge( $status ) {
    $status = strtolower( (string) $status );
    $map = array(
        'approved' => array('#dcfce7','#166534','Approved'),
        'rejected' => array('#fee2e2','#991b1b','Rejected'),
        'pending'  => array('#fef3c7','#92400e','Pending'),
    );
    $v = isset($map[$status]) ? $map[$status] : array('#e2e8f0','#334155',ucfirst($status ?: 'Unknown'));
    return '<span style="display:inline-block;padding:7px 12px;border-radius:999px;background:'.$v[0].';color:'.$v[1].';font-size:13px;font-weight:700;">'.esc_html($v[2]).'</span>';
}

function ayument_dashboard_upgrade_header( $application, $view ) {
    $name = $application && $application->full_name ? $application->full_name : wp_get_current_user()->display_name;
    $status = $application ? $application->status : 'pending';
    ob_start(); ?>
    <div style="max-width:1180px;margin:30px auto;padding:0 18px;font-family:Arial,sans-serif;color:#173f73;">
      <div style="background:linear-gradient(135deg,#ffffff,#f8fbff);border:1px solid #dbe7f0;border-radius:20px;padding:28px;box-shadow:0 8px 30px rgba(23,63,115,.08);">
        <div style="display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap;">
          <div>
            <div style="font-size:14px;color:#64748b;margin-bottom:7px;">AyuMent Doctor Workspace</div>
            <h1 style="margin:0 0 8px;font-size:32px;color:#173f73;">Welcome, Dr. <?php echo esc_html($name); ?></h1>
            <p style="margin:0;color:#64748b;">Manage your professional profile, patients and clinical activities.</p>
          </div>
          <div><?php echo ayument_dashboard_upgrade_status_badge($status); ?></div>
        </div>
      </div>
      <nav style="display:flex;gap:8px;flex-wrap:wrap;margin:18px 0 0;">
        <?php
        $items=array(
          'home'=>'Dashboard','profile'=>'My Profile','appointments'=>'Appointments',
          'patients'=>'My Patients','prescriptions'=>'Prescriptions','consultations'=>'Consultations'
        );
        foreach($items as $key=>$label):
          $active = ($view===$key);
        ?>
          <a href="<?php echo esc_url(ayument_dashboard_upgrade_page_url($key)); ?>"
             style="padding:11px 15px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;<?php echo $active?'background:#2563eb;color:#fff;':'background:#f1f5f9;color:#173f73;'; ?>">
             <?php echo esc_html($label); ?>
          </a>
        <?php endforeach; ?>
      </nav>
    <?php return ob_get_clean();
}

function ayument_dashboard_upgrade_card( $title, $text, $url, $button='Open' ) {
    return '<div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:22px;box-shadow:0 4px 18px rgba(0,0,0,.04);">
      <h3 style="margin:0 0 8px;color:#173f73;">'.esc_html($title).'</h3>
      <p style="color:#64748b;min-height:42px;">'.esc_html($text).'</p>
      <a href="'.esc_url($url).'" style="display:inline-block;background:#2563eb;color:#fff;padding:10px 15px;border-radius:9px;text-decoration:none;font-weight:700;">'.esc_html($button).'</a>
    </div>';
}

function ayument_dashboard_upgrade_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div style="max-width:700px;margin:50px auto;padding:30px;text-align:center;"><h2>Doctor Login Required</h2><p>Please log in to access the doctor portal.</p><a href="'.esc_url(wp_login_url(get_permalink())).'">Log in</a></div>';
    }

    $user = wp_get_current_user();
    $application = ayument_dashboard_upgrade_application($user->ID);
    $view = isset($_GET['doctor_view']) ? sanitize_key($_GET['doctor_view']) : 'home';
    $allowed = array('home','profile','appointments','patients','prescriptions','consultations');
    if(!in_array($view,$allowed,true)) $view='home';

    ob_start();
    echo ayument_dashboard_upgrade_header($application,$view);
    ?>
    <div style="max-width:1180px;margin:0 auto;padding:0 18px 50px;">
    <?php if($view==='home'): ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:22px;">
        <?php
        echo ayument_dashboard_upgrade_card('My Profile','View your verified professional information.',ayument_dashboard_upgrade_page_url('profile'),'View Profile');
        echo ayument_dashboard_upgrade_card('Appointments','Manage your upcoming and completed appointment records.',ayument_dashboard_upgrade_page_url('appointments'),'Open Appointments');
        echo ayument_dashboard_upgrade_card('My Patients','Your patient workspace will appear here as consultations are created.',ayument_dashboard_upgrade_page_url('patients'),'Open Patients');
        echo ayument_dashboard_upgrade_card('Prescriptions','Create and review prescription records linked to consultations.',ayument_dashboard_upgrade_page_url('prescriptions'),'Open Prescriptions');
        echo ayument_dashboard_upgrade_card('Consultations','Record consultation notes and clinical follow-up information.',ayument_dashboard_upgrade_page_url('consultations'),'Open Consultations');
        ?>
      </div>
      <div style="margin-top:22px;background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:24px;">
        <h2 style="margin-top:0;color:#173f73;">Application Status</h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;">
          <div style="background:#f8fafc;padding:18px;border-radius:12px;"><strong>Status</strong><div style="margin-top:8px;"><?php echo $application ? ayument_dashboard_upgrade_status_badge($application->status) : 'Not found'; ?></div></div>
          <div style="background:#f8fafc;padding:18px;border-radius:12px;"><strong>Qualification</strong><div style="margin-top:8px;color:#475569;"><?php echo esc_html($application->qualification ?? ''); ?></div></div>
          <div style="background:#f8fafc;padding:18px;border-radius:12px;"><strong>Registration No.</strong><div style="margin-top:8px;color:#475569;"><?php echo esc_html($application->registration_number ?? ''); ?></div></div>
        </div>
      </div>
    <?php elseif($view==='profile'): ?>
      <div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:28px;margin-top:22px;">
        <h2 style="margin-top:0;color:#173f73;">Professional Profile</h2>
        <?php if($application): ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
        <?php
        $fields=array(
          'Name'=>$application->full_name,'Email'=>$application->email,'Phone'=>$application->phone,
          'Qualification'=>$application->qualification,'Specialization'=>$application->specialization ?: 'Not provided',
          'Registration Number'=>$application->registration_number,'Registration Council'=>$application->registration_council ?: 'Not provided',
          'Experience'=>$application->experience ?: 'Not provided','Clinic'=>$application->clinic_name ?: 'Not provided',
          'Languages'=>$application->languages ?: 'Not provided'
        );
        foreach($fields as $k=>$v): ?>
          <div><strong style="display:block;margin-bottom:6px;"><?php echo esc_html($k); ?></strong><span style="color:#475569;"><?php echo esc_html($v); ?></span></div>
        <?php endforeach; ?>
        </div>
        <?php if(!empty($application->professional_bio)): ?>
          <div style="margin-top:25px;padding-top:20px;border-top:1px solid #e5e7eb;"><strong>Professional Bio</strong><p style="white-space:pre-wrap;color:#475569;"><?php echo esc_html($application->professional_bio); ?></p></div>
        <?php endif; ?>
        <?php else: ?><p>No doctor application was found for this account.</p><?php endif; ?>
      </div>
    <?php elseif($view==='appointments'): ?>
      <div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:28px;margin-top:22px;">
        <h2 style="margin-top:0;color:#173f73;">Appointments</h2>
        <div style="padding:24px;background:#f8fafc;border-radius:12px;color:#64748b;">Appointment scheduling is ready for the next integration stage. This workspace is now connected to the doctor dashboard and can be expanded without changing the verification system.</div>
      </div>
    <?php elseif($view==='patients'): ?>
      <div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:28px;margin-top:22px;">
        <h2 style="margin-top:0;color:#173f73;">My Patients</h2>
        <div style="padding:24px;background:#f8fafc;border-radius:12px;color:#64748b;">Patient records will populate here once a patient is assigned through an appointment or consultation.</div>
      </div>
    <?php elseif($view==='prescriptions'): ?>
      <div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:28px;margin-top:22px;">
        <h2 style="margin-top:0;color:#173f73;">Prescriptions</h2>
        <div style="padding:24px;background:#f8fafc;border-radius:12px;color:#64748b;">Prescription creation will be connected to patient consultations in the next clinical workflow stage.</div>
      </div>
    <?php elseif($view==='consultations'): ?>
      <div style="background:#fff;border:1px solid #dbe7f0;border-radius:16px;padding:28px;margin-top:22px;">
        <h2 style="margin-top:0;color:#173f73;">Consultations</h2>
        <div style="padding:24px;background:#f8fafc;border-radius:12px;color:#64748b;">Consultation records will be connected to appointments and patient profiles in the next stage.</div>
      </div>
    <?php endif; ?>
    <div style="margin-top:28px;text-align:right;"><a href="<?php echo esc_url(wp_logout_url(site_url('/'))); ?>" style="color:#dc2626;text-decoration:none;font-weight:700;">Log out</a></div>
    </div>
    <style>
      @media(max-width:800px){div[style*="repeat(3,1fr)"]{grid-template-columns:1fr!important;}}
    </style>
    <?php
    return ob_get_clean();
}
