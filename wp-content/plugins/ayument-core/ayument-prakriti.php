<?php
/** AyuMent Professional Prakriti Assessment - 60 MCQs. */
if (!defined('ABSPATH')) { exit; }
function ayument_prakriti_menu() {
    add_submenu_page('ayument-dashboard','Prakriti Assessment','Prakriti Assessment','manage_options','ayument-prakriti','ayument_prakriti_page');
}
add_action('admin_menu','ayument_prakriti_menu');
function ayument_prakriti_questions() { return array(
        array('q' => 'Body frame', 'a' => 'Thin, lean', 'b' => 'Medium, muscular', 'c' => 'Broad, heavy'),
        array('q' => 'Body weight', 'a' => 'Difficult to gain', 'b' => 'Stable', 'c' => 'Gains weight easily'),
        array('q' => 'Skin', 'a' => 'Dry, rough', 'b' => 'Soft, warm, reddish', 'c' => 'Smooth, thick, oily'),
        array('q' => 'Hair', 'a' => 'Dry, coarse, curly', 'b' => 'Fine, straight, early greying', 'c' => 'Thick, oily, dense'),
        array('q' => 'Eyes', 'a' => 'Small, dry, active', 'b' => 'Sharp, penetrating', 'c' => 'Large, calm'),
        array('q' => 'Appetite', 'a' => 'Irregular', 'b' => 'Strong', 'c' => 'Moderate'),
        array('q' => 'Digestion', 'a' => 'Variable', 'b' => 'Fast', 'c' => 'Slow'),
        array('q' => 'Thirst', 'a' => 'Variable', 'b' => 'Excessive', 'c' => 'Less'),
        array('q' => 'Bowel habit', 'a' => 'Constipation', 'b' => 'Soft/frequent', 'c' => 'Regular'),
        array('q' => 'Sweating', 'a' => 'Minimal', 'b' => 'Profuse', 'c' => 'Moderate'),
        array('q' => 'Climate', 'a' => 'Likes warmth', 'b' => 'Prefers cool', 'c' => 'Likes warm/dry'),
        array('q' => 'Sleep', 'a' => 'Light', 'b' => 'Moderate', 'c' => 'Deep'),
        array('q' => 'Walking', 'a' => 'Fast', 'b' => 'Purposeful', 'c' => 'Slow'),
        array('q' => 'Speech', 'a' => 'Fast', 'b' => 'Clear', 'c' => 'Calm'),
        array('q' => 'Energy', 'a' => 'Bursts', 'b' => 'Intense', 'c' => 'Steady'),
        array('q' => 'Memory', 'a' => 'Quick learn/forget', 'b' => 'Good', 'c' => 'Slow learn/long retain'),
        array('q' => 'Decision', 'a' => 'Indecisive', 'b' => 'Quick', 'c' => 'Slow but firm'),
        array('q' => 'Emotion', 'a' => 'Anxiety', 'b' => 'Anger', 'c' => 'Calm'),
        array('q' => 'Work', 'a' => 'Creative', 'b' => 'Competitive', 'c' => 'Methodical'),
        array('q' => 'Social', 'a' => 'Talkative', 'b' => 'Assertive', 'c' => 'Supportive'),
        array('q' => 'Tolerance to fasting', 'a' => 'Poor', 'b' => 'Moderate', 'c' => 'Good'),
        array('q' => 'Physical strength', 'a' => 'Low', 'b' => 'Moderate', 'c' => 'High'),
        array('q' => 'Endurance', 'a' => 'Low', 'b' => 'Moderate', 'c' => 'Excellent'),
        array('q' => 'Immunity', 'a' => 'Variable', 'b' => 'Moderate', 'c' => 'Strong'),
        array('q' => 'Recovery after illness', 'a' => 'Slow', 'b' => 'Moderate', 'c' => 'Fast'),
        array('q' => 'Body temperature', 'a' => 'Cold', 'b' => 'Warm', 'c' => 'Cool'),
        array('q' => 'Hands and feet', 'a' => 'Cold', 'b' => 'Warm', 'c' => 'Cool/moist'),
        array('q' => 'Nails', 'a' => 'Dry/brittle', 'b' => 'Pink/soft', 'c' => 'Thick/smooth'),
        array('q' => 'Teeth', 'a' => 'Irregular', 'b' => 'Medium', 'c' => 'Large/strong'),
        array('q' => 'Lips', 'a' => 'Dry', 'b' => 'Reddish', 'c' => 'Full/moist'),
        array('q' => 'Voice', 'a' => 'Low/hoarse', 'b' => 'Sharp', 'c' => 'Deep/smooth'),
        array('q' => 'Dreams', 'a' => 'Fear/travel', 'b' => 'Fire/conflict', 'c' => 'Water/nature'),
        array('q' => 'Concentration', 'a' => 'Variable', 'b' => 'Focused', 'c' => 'Steady'),
        array('q' => 'Patience', 'a' => 'Low', 'b' => 'Moderate', 'c' => 'High'),
        array('q' => 'Response to stress', 'a' => 'Anxious', 'b' => 'Irritable', 'c' => 'Withdrawn/calm'),
        array('q' => 'Food preference', 'a' => 'Warm/oily', 'b' => 'Cool foods', 'c' => 'Light/spicy'),
        array('q' => 'Taste preference', 'a' => 'Sweet/sour/salty', 'b' => 'Sweet/bitter', 'c' => 'Pungent/astringent'),
        array('q' => 'Season disliked', 'a' => 'Cold/windy', 'b' => 'Hot/summer', 'c' => 'Cold/damp'),
        array('q' => 'Motivation', 'a' => 'Starts quickly', 'b' => 'Goal-oriented', 'c' => 'Slow but persistent'),
        array('q' => 'Lifestyle', 'a' => 'Irregular', 'b' => 'Structured', 'c' => 'Routine-loving'),
        array('q' => 'Work pace', 'a' => 'Fast but variable', 'b' => 'Fast and organized', 'c' => 'Slow but steady'),
        array('q' => 'Financial habits', 'a' => 'Spends impulsively', 'b' => 'Spends on quality', 'c' => 'Saves regularly'),
        array('q' => 'Adaptability', 'a' => 'Adapts quickly', 'b' => 'Adapts with planning', 'c' => 'Prefers stability'),
        array('q' => 'Leadership style', 'a' => 'Inspirational', 'b' => 'Directive', 'c' => 'Supportive'),
        array('q' => 'Learning style', 'a' => 'Quick grasp', 'b' => 'Analytical', 'c' => 'Repetitive practice'),
        array('q' => 'Reaction to criticism', 'a' => 'Worried', 'b' => 'Defensive', 'c' => 'Accepts calmly'),
        array('q' => 'Friendships', 'a' => 'Many but short-term', 'b' => 'Selective', 'c' => 'Few but long-lasting'),
        array('q' => 'Emotional recovery', 'a' => 'Quick mood changes', 'b' => 'Moderate', 'c' => 'Slow but stable'),
        array('q' => 'Exercise capacity', 'a' => 'Low endurance', 'b' => 'Moderate-high', 'c' => 'High endurance'),
        array('q' => 'Perspiration odor', 'a' => 'Minimal', 'b' => 'Strong', 'c' => 'Mild'),
        array('q' => 'Disease tendency', 'a' => 'Neurological/joint issues', 'b' => 'Inflammatory disorders', 'c' => 'Congestion/weight gain'),
        array('q' => 'Season of best health', 'a' => 'Summer', 'b' => 'Winter', 'c' => 'Spring'),
        array('q' => 'Speech during stress', 'a' => 'Rapid', 'b' => 'Loud', 'c' => 'Quiet'),
        array('q' => 'Facial appearance', 'a' => 'Thin', 'b' => 'Sharp features', 'c' => 'Round/full'),
        array('q' => 'Pulse tendency', 'a' => 'Irregular', 'b' => 'Strong', 'c' => 'Slow and steady'),
        array('q' => 'Sexual drive', 'a' => 'Variable', 'b' => 'Strong', 'c' => 'Steady'),
        array('q' => 'Sleep after stress', 'a' => 'Difficulty sleeping', 'b' => 'Disturbed', 'c' => 'Sleeps more'),
        array('q' => 'Overall temperament', 'a' => 'Creative', 'b' => 'Ambitious', 'c' => 'Patient'),
        array('q' => 'Daily routine', 'a' => 'Irregular', 'b' => 'Well planned', 'c' => 'Highly consistent'),
        array('q' => 'Overall self-description', 'a' => 'Active and imaginative', 'b' => 'Focused and determined', 'c' => 'Calm and dependable'),
    ); }
function ayument_prakriti_page() {
    $questions=ayument_prakriti_questions(); $submitted=isset($_POST['ayument_prakriti_submit']);
    $scores=array('Vata'=>0,'Pitta'=>0,'Kapha'=>0); $error='';
    if($submitted) {
        if(!isset($_POST['ayument_prakriti_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ayument_prakriti_nonce'])),'ayument_prakriti_assessment')) { $error='Security check failed. Please reload the assessment.'; }
        else { $posted=isset($_POST['prakriti_answer'])?(array)$_POST['prakriti_answer']:array(); foreach($questions as $i=>$q) { $v=isset($posted[$i])?sanitize_text_field(wp_unslash($posted[$i])):''; if(!in_array($v,array('A','B','C'),true)) { $error='Please answer every question before submitting.'; break; } if($v==='A')$scores['Vata']++; elseif($v==='B')$scores['Pitta']++; else $scores['Kapha']++; } }
    }
    $total=array_sum($scores); $pct=array(); foreach($scores as $k=>$v)$pct[$k]=$total?round($v/$total*100):0; arsort($scores); $r=array_keys($scores); $primary=$r[0]; $secondary=$r[1]; $profile=(($scores[$primary]-$scores[$secondary])<=5)?$primary.'-'.$secondary.' predominant':$primary.' predominant';
    ?>
    <div class="wrap ayument-prakriti-wrap">
    <style>
    .ayument-prakriti-wrap{max-width:1050px;margin:25px auto}.ayument-prakriti-hero{background:linear-gradient(135deg,#315c45,#4f8065);color:#fff;padding:30px;border-radius:16px;margin-bottom:22px;box-shadow:0 5px 18px rgba(0,0,0,.1)}.ayument-prakriti-hero h1{color:#fff;margin:0 0 8px;font-size:30px}.ayument-prakriti-hero p{margin:0;font-size:15px;line-height:1.6}.ayument-prakriti-card{background:#fff;border:1px solid #dfe8e2;border-radius:14px;padding:25px;box-shadow:0 4px 14px rgba(0,0,0,.05);margin-bottom:20px}.ayument-progress{height:8px;background:#e7eee9;border-radius:10px;overflow:hidden;margin:15px 0 25px}.ayument-progress-bar{height:100%;width:0;background:#315c45;transition:width .2s}.ayument-question{display:none}.ayument-question.active{display:block}.ayument-question h2{margin-top:0;color:#26372d;font-size:21px}.ayument-option{display:block;border:1px solid #dfe8e2;border-radius:10px;padding:14px 16px;margin:10px 0;cursor:pointer;background:#fbfdfb}.ayument-option:hover{border-color:#315c45;background:#f4f9f5}.ayument-option input{margin-right:10px}.ayument-nav{display:flex;justify-content:space-between;gap:10px;margin-top:25px}.ayument-result-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin:20px 0}.ayument-score{border:1px solid #dfe8e2;border-radius:12px;padding:18px;text-align:center;background:#fbfdfb}.ayument-score strong{display:block;font-size:28px;color:#315c45;margin-top:5px}.ayument-result-title{font-size:26px;margin-bottom:8px;color:#315c45}.ayument-disclaimer{background:#f7f7f7;border-left:4px solid #315c45;padding:15px;line-height:1.6;font-size:13px}.ayument-error{background:#fff1f1;border-left:4px solid #c0392b;padding:15px;margin-bottom:20px}.ayument-result-kicker{font-size:12px;letter-spacing:1.5px;font-weight:700;color:#6b7d72;margin-bottom:6px}.ayument-profile-badge{display:inline-block;background:#edf6ef;color:#315c45;border:1px solid #cfe1d4;border-radius:999px;padding:9px 15px;font-weight:700;margin:5px 0 15px}.ayument-result-summary{font-size:16px;line-height:1.7}.ayument-score-primary{border-color:#315c45;background:#f1f8f3;box-shadow:0 0 0 2px rgba(49,92,69,.08)}.ayument-score span{font-weight:600}.ayument-score small{display:block;color:#6b7d72;margin-top:5px}.ayument-result-sections{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin:22px 0}.ayument-result-box{background:#f8fbf9;border:1px solid #dfe8e2;border-radius:12px;padding:18px}.ayument-result-box h3{margin:0 0 9px;color:#315c45;font-size:17px}.ayument-result-box p{line-height:1.65;margin:0 0 7px}.ayument-result-actions{margin-top:20px}@media(max-width:700px){.ayument-result-grid,.ayument-result-sections{grid-template-columns:1fr}}
    </style>
    <div class="ayument-prakriti-hero"><h1>🌿 Prakriti Assessment</h1><p>Ayument Professional Prakriti Assessment · 60 questions</p></div>
    <?php if($error): ?><div class="ayument-error"><?php echo esc_html($error); ?></div><?php endif; ?>
    <?php if($submitted && !$error): ?>
      <?php
      $prakriti_info=array(
        'Vata'=>array(
          'icon'=>'🌬️','tone'=>'Movement, creativity and adaptability',
          'summary'=>'Your responses show stronger Vata-associated characteristics such as variability, quick activity, creativity and sensitivity to changes in routine.',
          'helpful'=>'Regular meals, adequate rest, warmth, a predictable routine and calming activities may be supportive.',
          'watch'=>'Irregular routines, excessive stimulation, skipped meals and inadequate sleep may be less supportive.'
        ),
        'Pitta'=>array(
          'icon'=>'🔥','tone'=>'Transformation, focus and intensity',
          'summary'=>'Your responses show stronger Pitta-associated characteristics such as focus, intensity, strong appetite and goal-oriented behaviour.',
          'helpful'=>'A balanced routine, adequate hydration, moderation, cooling/restful activities and avoiding excessive heat may be supportive.',
          'watch'=>'Excessive heat, overwork, irritability and highly intense routines may be less supportive.'
        ),
        'Kapha'=>array(
          'icon'=>'🌿','tone'=>'Stability, strength and endurance',
          'summary'=>'Your responses show stronger Kapha-associated characteristics such as steadiness, patience, endurance and preference for consistency.',
          'helpful'=>'Regular movement, a stimulating routine, appropriate activity and avoiding excessive inactivity may be supportive.',
          'watch'=>'Prolonged inactivity, excessive heaviness in routine and overeating may be less supportive.'
        )
      );
      $pi=$prakriti_info[$primary];
      $secondary_info=$prakriti_info[$secondary];
      ?>
      <div class="ayument-prakriti-card ayument-result-main">
        <div class="ayument-result-kicker">ASSESSMENT COMPLETE</div>
        <div class="ayument-result-title"><?php echo esc_html($pi['icon']); ?> Your Prakriti Profile</div>
        <div class="ayument-profile-badge"><?php echo esc_html($profile); ?></div>
        <p class="ayument-result-summary"><?php echo esc_html($pi['summary']); ?></p>

        <div class="ayument-result-grid">
        <?php foreach(array('Vata','Pitta','Kapha') as $d): ?>
          <div class="ayument-score <?php echo $d===$primary?'ayument-score-primary':''; ?>">
            <span><?php echo esc_html($d); ?></span>
            <strong><?php echo esc_html($pct[$d]); ?>%</strong>
            <small><?php echo esc_html($scores[$d]); ?> / <?php echo count($questions); ?> responses</small>
          </div>
        <?php endforeach; ?>
        </div>

        <div class="ayument-result-sections">
          <div class="ayument-result-box"><h3>🌿 What this indicates</h3><p><strong><?php echo esc_html($primary); ?>:</strong> <?php echo esc_html($pi['tone']); ?>.</p><p>Your secondary influence is <strong><?php echo esc_html($secondary); ?></strong>, so your profile may also reflect some <?php echo esc_html(strtolower($secondary_info['tone'])); ?>.</p></div>
          <div class="ayument-result-box"><h3>✓ Generally supportive habits</h3><p><?php echo esc_html($pi['helpful']); ?></p></div>
          <div class="ayument-result-box"><h3>⚠️ Things to be mindful of</h3><p><?php echo esc_html($pi['watch']); ?></p></div>
        </div>

        <div class="ayument-disclaimer"><strong>Important:</strong> This is an educational Ayurvedic constitution assessment based on the supplied questionnaire. It is not a medical or psychiatric diagnosis, does not establish a disease or dosha imbalance, and should not replace professional clinical evaluation.</div>
        <div class="ayument-result-actions"><a href="<?php echo esc_url(admin_url('admin.php?page=ayument-prakriti')); ?>" class="button button-primary">↻ Retake Assessment</a></div>
      </div>
    <?php else: ?>
      <form method="post" id="ayument-prakriti-form"><?php wp_nonce_field('ayument_prakriti_assessment','ayument_prakriti_nonce'); ?><div class="ayument-prakriti-card"><div>Question <span id="ayument-current">1</span> of <?php echo count($questions); ?></div><div class="ayument-progress"><div class="ayument-progress-bar" id="ayument-progress-bar"></div></div>
      <?php foreach($questions as $i=>$q): ?><div class="ayument-question<?php echo $i===0?' active':''; ?>" data-question="<?php echo esc_attr($i); ?>"><h2><?php echo esc_html(($i+1).'. '.$q['q']); ?></h2><label class="ayument-option"><input type="radio" name="prakriti_answer[<?php echo esc_attr($i); ?>]" value="A">A. <?php echo esc_html($q['a']); ?></label><label class="ayument-option"><input type="radio" name="prakriti_answer[<?php echo esc_attr($i); ?>]" value="B">B. <?php echo esc_html($q['b']); ?></label><label class="ayument-option"><input type="radio" name="prakriti_answer[<?php echo esc_attr($i); ?>]" value="C">C. <?php echo esc_html($q['c']); ?></label></div><?php endforeach; ?>
      <div class="ayument-nav"><button type="button" class="button" id="ayument-prev">← Previous</button><button type="button" class="button button-primary" id="ayument-next">Next →</button><button type="submit" name="ayument_prakriti_submit" value="1" class="button button-primary" id="ayument-submit" style="display:none">Calculate Prakriti</button></div></div></form>
      <div class="ayument-prakriti-card"><div class="ayument-disclaimer"><strong>About this assessment:</strong> Questions and answer mapping are based on the user-provided “Ayument Professional Prakriti Assessment (MCQ)”.</div></div>
    <?php endif; ?></div>
    <?php if(!$submitted || $error): ?><script>document.addEventListener('DOMContentLoaded',function(){const qs=[...document.querySelectorAll('.ayument-question')],prev=document.getElementById('ayument-prev'),next=document.getElementById('ayument-next'),submit=document.getElementById('ayument-submit'),cur=document.getElementById('ayument-current'),bar=document.getElementById('ayument-progress-bar');if(!qs.length)return;let i=0;function show(){qs.forEach((q,n)=>q.classList.toggle('active',n===i));cur.textContent=i+1;bar.style.width=((i+1)/qs.length*100)+'%';prev.style.visibility=i===0?'hidden':'visible';next.style.display=i===qs.length-1?'none':'inline-block';submit.style.display=i===qs.length-1?'inline-block':'none'}next.onclick=function(){if(!qs[i].querySelector('input:checked')){alert('Please select an answer before continuing.');return}if(i<qs.length-1){i++;show();window.scrollTo({top:0,behavior:'smooth'})}};prev.onclick=function(){if(i>0){i--;show();window.scrollTo({top:0,behavior:'smooth'})}};document.getElementById('ayument-prakriti-form').onsubmit=function(e){for(let n=0;n<qs.length;n++)if(!qs[n].querySelector('input:checked')){e.preventDefault();i=n;show();alert('Please answer all questions before submitting.');return}};show()});</script><?php endif; ?>
    <?php
}
