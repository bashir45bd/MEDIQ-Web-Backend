<?php
$activePage = 'queue';
$pageTitle = 'Digital Prescription & Consultation Editor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Consultation & Prescription — MEDIQ</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .med-row {
      display: grid;
      grid-template-columns: 2fr 1.1fr 1.3fr 1.1fr 1.6fr 36px;
      gap: 8px;
      margin-bottom: 10px;
      align-items: center;
      background: #f8fafc;
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
    }
    .chip-container {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }
    .chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 9999px;
      font-size: 12px;
      color: #334155;
      cursor: pointer;
      user-select: none;
      transition: all 0.15s ease-in-out;
    }
    .chip:hover {
      background: #e0f2fe;
      border-color: #0284c7;
      color: #0284c7;
      transform: translateY(-1px);
    }
    .chip-primary {
      background: #e0f2fe;
      border-color: #bae6fd;
      color: #0369a1;
      font-weight: 600;
    }
    .chip-primary:hover {
      background: #0284c7;
      color: #ffffff;
    }
    .section-helper {
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 6px;
    }
    .quick-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-body">
      <div id="noticeBox" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"></div>

      <!-- Patient Header Info Banner -->
      <div class="card" style="margin-bottom: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 18px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div>
            <span style="font-size: 12px; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: 0.5px;">Patient Under Consultation</span>
            <h3 id="patientName" style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;">Loading patient...</h3>
            <div id="patientMeta" style="font-size: 13.5px; color: #475569; margin-top: 2px;">Age / Gender / Blood Group</div>
          </div>
          <div style="text-align: right;">
            <span style="font-size: 12px; color: #15803d; font-weight: 600; display: block;">SERIAL TOKEN</span>
            <span id="patientToken" style="font-size: 32px; font-weight: 900; color: #16a34a;">--</span>
          </div>
        </div>
      </div>

      <form id="consultationForm">
        <!-- 1. Symptoms & Examination Notes (With 1-Click Selectable Chips) -->
        <div class="grid grid-cols-2" style="margin-bottom: 20px;">
          <!-- Presenting Symptoms -->
          <div class="card">
            <div class="quick-bar">
              <h4 style="font-size: 15px; font-weight: 700;">Chief Complaints (C/C)</h4>
              <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; color: var(--text-muted);">Duration:</span>
                <select id="symptomDurationSelect" class="form-control" style="width: auto; padding: 2px 6px; font-size: 12px; height: 26px;">
                  <option value="for 1 day">1 day</option>
                  <option value="for 2 days">2 days</option>
                  <option value="for 3 days" selected>3 days</option>
                  <option value="for 5 days">5 days</option>
                  <option value="for 1 week">1 week</option>
                  <option value="for 2 weeks">2 weeks</option>
                  <option value="for 1 month">1 month</option>
                </select>
              </div>
            </div>
            <textarea id="symptoms" class="form-control" rows="3" placeholder="Click any symptom chip below to add with 1 click, or type custom notes..."></textarea>
            
            <div class="chip-container" id="symptomsChips">
              <span class="chip" data-val="Fever (জ্বর)">+ Fever (জ্বর)</span>
              <span class="chip" data-val="Dry Cough (শুকনো কাশি)">+ Dry Cough</span>
              <span class="chip" data-val="Productive Cough (কাশি ও কফ)">+ Productive Cough</span>
              <span class="chip" data-val="Chest pain on exertion (বুকে ব্যথা)">+ Chest Pain</span>
              <span class="chip" data-val="Shortness of breath (শ্বাসকষ্ট)">+ Shortness of Breath</span>
              <span class="chip" data-val="Severe Headache (মাথাব্যথা)">+ Headache</span>
              <span class="chip" data-val="Abdominal pain (পেটে ব্যথা)">+ Abdominal Pain</span>
              <span class="chip" data-val="Vomiting & Nausea (বমি ভাব)">+ Vomiting</span>
              <span class="chip" data-val="Loose Motion (পাতলা পায়খানা)">+ Loose Motion</span>
              <span class="chip" data-val="Generalized Weakness (দুর্বলতা)">+ Fatigue</span>
              <span class="chip" data-val="Joint pain (হাতে-পায়ে ব্যথা)">+ Joint Pain</span>
              <span class="chip" data-val="Burning micturition (প্রস্রাবে জ্বালা)">+ Burning Micturition</span>
              <span class="chip" data-val="Palpitation & High BP (বুক ধড়ফড়)">+ Palpitation</span>
              <span class="chip" data-val="Loss of appetite (অরুচি)">+ Anorexia</span>
            </div>
          </div>

          <!-- Clinical Examination Findings (O/E) -->
          <div class="card">
            <div class="quick-bar">
              <h4 style="font-size: 15px; font-weight: 700;">On Examination (O/E)</h4>
              <button type="button" id="btnNormalVitals" class="btn btn-outline btn-sm" style="font-size: 11.5px; padding: 3px 8px; background: #f0fdf4; color: #16a34a; border-color: #86efac;">
                ⚡ Normal Template
              </button>
            </div>
            <textarea id="clinicalNotes" class="form-control" rows="3" placeholder="Click vitals chips below or insert normal template..."></textarea>
            
            <div class="chip-container" id="vitalsChips">
              <span class="chip chip-primary" data-val="BP: 120/80 mmHg">BP: 120/80</span>
              <span class="chip chip-primary" data-val="BP: 130/85 mmHg">BP: 130/85</span>
              <span class="chip chip-primary" data-val="BP: 140/90 mmHg">BP: 140/90</span>
              <span class="chip chip-primary" data-val="BP: 150/95 mmHg">BP: 150/95</span>
              <span class="chip" data-val="Pulse: 72 bpm, regular">Pulse: 72 bpm</span>
              <span class="chip" data-val="Pulse: 84 bpm, regular">Pulse: 84 bpm</span>
              <span class="chip" data-val="Temp: 98.4°F (Afebrile)">Temp: 98.4°F</span>
              <span class="chip" data-val="Temp: 101°F (Febrile)">Temp: 101°F</span>
              <span class="chip" data-val="Chest: Clear, vesicular breath sounds">Chest: Clear</span>
              <span class="chip" data-val="Chest: Bilateral rhonchi / wheeze present">Chest: Wheeze</span>
              <span class="chip" data-val="Heart: S1 + S2 heard, no murmur">Heart: Normal</span>
              <span class="chip" data-val="Abdomen: Soft, non-tender">Abdomen: Soft</span>
              <span class="chip" data-val="Edema: Bilateral pedal edema present">Edema: Present</span>
            </div>
          </div>
        </div>

        <!-- 2. Diagnosis (ICD-10) with Dropdown Selector -->
        <div class="card" style="margin-bottom: 20px;">
          <div class="quick-bar">
            <h4 style="font-size: 15px; font-weight: 700;">Provisional Diagnosis (Dx)</h4>
            <span class="section-helper">Choose from preset list or type manually</span>
          </div>

          <!-- Quick Select Dropdown -->
          <div style="margin-bottom: 10px;">
            <select id="quickDiagSelect" class="form-control" style="background: #f8fafc; font-weight: 600; color: #0284c7;">
              <option value="">-- Quick Select Common Diagnosis (1-Click Fill) --</option>
              <option value="Essential Hypertension|I10">Essential Hypertension (উচ্চ রক্তচাপ) [I10]</option>
              <option value="Type 2 Diabetes Mellitus|E11">Type 2 Diabetes Mellitus (ডায়াবেটিস) [E11]</option>
              <option value="Acute Upper Respiratory Tract Infection (URI)|J06.9">Acute Upper Respiratory Infection (ঠান্ডা/কাশি) [J06.9]</option>
              <option value="Acute Bronchitis|J20.9">Acute Bronchitis [J20.9]</option>
              <option value="Bronchial Asthma|J45.9">Bronchial Asthma (অ্যাজমা/হাঁপানি) [J45.9]</option>
              <option value="Peptic Ulcer Disease / Dyspepsia|K27">Peptic Ulcer Disease / Dyspepsia (গ্যাস্ট্রিক) [K27]</option>
              <option value="Gastro-Esophageal Reflux Disease (GERD)|K21.9">Gastro-Esophageal Reflux Disease (GERD) [K21.9]</option>
              <option value="Ischaemic Heart Disease / Angina|I20.9">Ischaemic Heart Disease / Angina (হৃদরোগ) [I20.9]</option>
              <option value="Urinary Tract Infection (UTI)|N39.0">Urinary Tract Infection (ইউরিন ইনফেকশন) [N39.0]</option>
              <option value="Osteoarthritis / Arthralgia|M19.9">Osteoarthritis (বাত ব্যথা / গিঁটে ব্যথা) [M19.9]</option>
              <option value="Acute Gastroenteritis|A09">Acute Gastroenteritis (ডায়রিয়া/ফুড পয়জনিং) [A09]</option>
              <option value="Dengue Fever|A90">Dengue Fever (ডেঙ্গু জ্বর) [A90]</option>
              <option value="Enteric Fever / Typhoid|A01.0">Enteric Fever / Typhoid (টাইফয়েড) [A01.0]</option>
              <option value="Tension Headache / Migraine|G43.9">Migraine / Tension Headache (মাইগ্রেন) [G43.9]</option>
              <option value="Generalized Anxiety Disorder|F41.1">Generalized Anxiety Disorder (দুশ্চিন্তা) [F41.1]</option>
              <option value="Iron Deficiency Anaemia|D50.9">Iron Deficiency Anaemia (রক্তস্বল্পতা) [D50.9]</option>
              <option value="Allergic Rhinitis|J30.4">Allergic Rhinitis (এলার্জি) [J30.4]</option>
            </select>
          </div>

          <div style="display: flex; gap: 10px;">
            <input type="text" id="primaryDiagnosis" class="form-control" placeholder="Primary Diagnosis (e.g. Essential Hypertension)" required>
            <input type="text" id="icdCode" class="form-control" style="width: 140px;" placeholder="ICD-10 Code (e.g. I10)">
          </div>
        </div>

        <!-- 3. Prescription Drugs & Dosage (℞) with Fast Pre-fills -->
        <div class="card" style="margin-bottom: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <h4 style="font-size: 16px; display: flex; align-items: center; gap: 8px;">
              <span style="color: #0284c7; font-size: 26px; font-family: serif; font-weight: 900;">&#8478;</span>
              <span>Prescription Drugs & Dosage</span>
            </h4>

            <div style="display: flex; gap: 8px; align-items: center;">
              <!-- Quick Add Common Drug Dropdown -->
              <select id="quickMedSelect" class="form-control" style="width: auto; font-size: 13px; background: #f0fdf4; border-color: #86efac; color: #16a34a; font-weight: 600;">
                <option value="">⚡ + Quick Add Popular Medicine (1-Click)</option>
                <option value="Tab. Pantoprazole 20mg|1 tablet|1+0+1|14 days|Before meals (খাবারের ৩০ মি. আগে)">Tab. Pantoprazole 20mg (Gas/PPI)</option>
                <option value="Cap. Esomeprazole 20mg|1 capsule|1+0+1|14 days|Before meals (খাবারের ৩০ মি. আগে)">Cap. Esomeprazole 20mg (PPI)</option>
                <option value="Tab. Paracetamol 500mg|1 tablet|1+1+1|3 days|After meals (জ্বর বা ব্যথা হলে)">Tab. Paracetamol 500mg (Fever/Pain)</option>
                <option value="Tab. Paracetamol 665mg XR|1 tablet|1+0+1|3 days|After meals (ব্যথা হলে)">Tab. Paracetamol 665mg XR</option>
                <option value="Tab. Fexofenadine 120mg|1 tablet|0+0+1|7 days|At night after meals">Tab. Fexofenadine 120mg (Allergy)</option>
                <option value="Tab. Montelukast 10mg|1 tablet|0+0+1|14 days|At night after meals">Tab. Montelukast 10mg (Asthma/Cough)</option>
                <option value="Tab. Amlodipine 5mg|1 tablet|1+0+0|1 month|Morning after breakfast">Tab. Amlodipine 5mg (BP)</option>
                <option value="Tab. Losartan Potassium 50mg|1 tablet|0+0+1|1 month|Night after dinner">Tab. Losartan Potassium 50mg (BP)</option>
                <option value="Tab. Bisoprolol 2.5mg|1 tablet|1+0+0|1 month|Morning after breakfast">Tab. Bisoprolol 2.5mg (Pulse/BP)</option>
                <option value="Tab. Metformin 500mg|1 tablet|1+0+1|1 month|With meals (খাবারের সাথে)">Tab. Metformin 500mg (Diabetes)</option>
                <option value="Cap. Amoxicillin 500mg|1 capsule|1+0+1|7 days|After meals (পুরো কোর্স খাবেন)">Cap. Amoxicillin 500mg (Antibiotic)</option>
                <option value="Tab. Cefuroxime 500mg|1 tablet|1+0+1|7 days|After meals (পুরো কোর্স খাবেন)">Tab. Cefuroxime 500mg (Antibiotic)</option>
                <option value="Tab. Azithromycin 500mg|1 tablet|1+0+0|5 days|1 hour before meals">Tab. Azithromycin 500mg (Antibiotic)</option>
                <option value="Tab. Neuro-B (Vit B1+B6+B12)|1 tablet|1+0+1|1 month|After meals">Tab. Neuro-B (Vitamins)</option>
                <option value="Tab. Calcium 500mg + Vit D3|1 tablet|0+0+1|1 month|After dinner">Tab. Calcium 500mg + D3</option>
                <option value="Syp. Antacid (200ml)|2 spoonful (10ml)|1+1+1|7 days|1 hour after meals">Syp. Antacid (Acidity)</option>
              </select>

              <button type="button" id="btnAddMed" class="btn btn-outline btn-sm">+ Empty Row</button>
            </div>
          </div>

          <div style="font-size: 11.5px; color: var(--text-muted); display: grid; grid-template-columns: 2fr 1.1fr 1.3fr 1.1fr 1.6fr 36px; gap: 8px; margin-bottom: 6px; font-weight: 700; text-transform: uppercase;">
            <div>Medicine Name & Strength</div>
            <div>Dose</div>
            <div>Frequency / Schedule</div>
            <div>Duration</div>
            <div>Special Instructions</div>
            <div></div>
          </div>

          <div id="medicinesContainer">
            <!-- Row 1 Default (Both Selectable & Free-Typeable) -->
            <div class="med-row">
              <input type="text" class="form-control med-name" placeholder="e.g. Tab. Pantoprazole 20mg" value="Tab. Pantoprazole 20mg" list="commonMedsDatalist" required>
              <input type="text" class="form-control med-dose" placeholder="Dose (1 tab)" value="1 tablet" list="doseDatalist">
              <input type="text" class="form-control med-freq" placeholder="Freq (1+0+1)" value="1+0+1" list="freqDatalist">
              <input type="text" class="form-control med-dur" placeholder="Duration" value="14 days" list="durDatalist">
              <input type="text" class="form-control med-inst" placeholder="Instructions" value="Before meals (খাবারের ৩০ মি. আগে)" list="instDatalist">
              <button type="button" class="btn btn-outline btn-sm btn-remove-med" style="color: red; padding: 4px;">&times;</button>
            </div>
          </div>
        </div>

        <!-- 4. Investigations & Advice (With 1-Click Selectable Chips) -->
        <div class="grid grid-cols-2" style="margin-bottom: 20px;">
          <!-- Investigations -->
          <div class="card">
            <div class="quick-bar">
              <h4 style="font-size: 15px; font-weight: 700;">Investigations Advised</h4>
              <span class="section-helper">Click chips to add</span>
            </div>
            <textarea id="investigations" class="form-control" rows="3" placeholder="Click chips below to add diagnostic tests..."></textarea>
            
            <div class="chip-container" id="investigationChips">
              <span class="chip" data-val="CBC with ESR">+ CBC with ESR</span>
              <span class="chip" data-val="Blood Sugar (Fasting & 2h ABF)">+ Blood Sugar (F & 2h ABF)</span>
              <span class="chip" data-val="HbA1c">+ HbA1c</span>
              <span class="chip" data-val="Serum Creatinine">+ S. Creatinine</span>
              <span class="chip" data-val="Lipid Profile (Fasting)">+ Lipid Profile</span>
              <span class="chip" data-val="SGPT (ALT)">+ SGPT</span>
              <span class="chip" data-val="Urine R/M/E">+ Urine R/M/E</span>
              <span class="chip" data-val="ECG (12 Leads)">+ ECG (12 Leads)</span>
              <span class="chip" data-val="Echocardiography (2D & Color)">+ Echocardiography</span>
              <span class="chip" data-val="Chest X-Ray (P/A view)">+ Chest X-Ray P/A</span>
              <span class="chip" data-val="USG of Whole Abdomen">+ USG Whole Abdomen</span>
              <span class="chip" data-val="Serum Uric Acid">+ S. Uric Acid</span>
              <span class="chip" data-val="Serum Electrolytes">+ S. Electrolytes</span>
              <span class="chip" data-val="TSH (Thyroid Profile)">+ TSH</span>
            </div>
          </div>

          <!-- Doctor Advice -->
          <div class="card">
            <div class="quick-bar">
              <h4 style="font-size: 15px; font-weight: 700;">Doctor Advice (উপদেশ)</h4>
              <span class="section-helper">Click chips to add</span>
            </div>
            <textarea id="advice" class="form-control" rows="3" placeholder="Click chips below to add medical advice & lifestyle guidelines..."></textarea>
            
            <div class="chip-container" id="adviceChips">
              <span class="chip" data-val="Drink 2.5 - 3 Litres of clean water daily (প্রতিদিন পর্যাপ্ত পানি পান করুন)">+ Drink Plenty Water</span>
              <span class="chip" data-val="Low salt & low fat diet (লবণ ও চর্বিযুক্ত খাবার পরিহার করুন)">+ Low Salt & Fat Diet</span>
              <span class="chip" data-val="Daily 30 minutes brisk walking (প্রতিদিন ৩০ মিনিট হাঁটার অভ্যাস করুন)">+ Daily 30 min Walk</span>
              <span class="chip" data-val="Avoid smoking, tobacco, and alcohol (ধূমপান ও জর্দা সম্পূর্ণরূপে বর্জন করুন)">+ Avoid Smoking/Tobacco</span>
              <span class="chip" data-val="Avoid oily, spicy and fast food (তেল ও মশলাযুক্ত খাবার পরিহার করুন)">+ Avoid Oily/Spicy Food</span>
              <span class="chip" data-val="Maintain regular sleep schedule (7-8 hours) (নিয়মিত পর্যাপ্ত ঘুমান)">+ 7-8h Sleep</span>
              <span class="chip" data-val="Avoid heavy lifting & prolonged bending (ভারী জিনিস তোলা ও ঝুঁকে কাজ নিষেধ)">+ Avoid Heavy Lifting</span>
              <span class="chip" data-val="Check Blood Pressure weekly and record in diary (সাপ্তাহিক প্রেশার মাপুন)">+ Check BP Weekly</span>
              <span class="chip" data-val="Emergency: If severe chest pain or breathlessness occurs, visit hospital immediately">+ Emergency Notice</span>
            </div>
          </div>
        </div>

        <!-- 5. Follow-up Date & Submit Action Bar -->
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: #ffffff; border: 1.5px solid #0284c7;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <label class="form-label" style="margin: 0; font-weight: 700;" for="followUpDate">Next Follow-up Visit:</label>
            <input type="date" id="followUpDate" class="form-control" style="width: 170px; font-weight: 600;">
            <div style="display: flex; gap: 6px;">
              <button type="button" class="btn btn-outline btn-sm btn-quick-date" data-days="7">7 Days</button>
              <button type="button" class="btn btn-outline btn-sm btn-quick-date" data-days="14">14 Days</button>
              <button type="button" class="btn btn-outline btn-sm btn-quick-date" data-days="30">1 Month</button>
            </div>
          </div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="queue.php" class="btn btn-outline">Back to Queue</a>
            <button type="button" id="btnPaperRxComplete" class="btn btn-outline" style="border-color: #059669; color: #059669; font-weight: 700; padding: 12px 20px;">
              ✍️ Complete (Paper Rx / প্রেসক্রিপশন ছাড়া)
            </button>
            <button type="submit" id="btnSaveRx" class="btn btn-primary" style="padding: 12px 24px; font-size: 15.5px; font-weight: 800; background: #0284c7;">
              &#10003; Save & Issue Digital Rx
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- Datalist for autocomplete if doctor types manually or selects from dropdown -->
<datalist id="commonMedsDatalist">
  <option value="Tab. Pantoprazole 20mg">
  <option value="Cap. Esomeprazole 20mg">
  <option value="Tab. Paracetamol 500mg">
  <option value="Tab. Paracetamol 665mg XR">
  <option value="Tab. Fexofenadine 120mg">
  <option value="Tab. Montelukast 10mg">
  <option value="Tab. Amlodipine 5mg">
  <option value="Tab. Losartan Potassium 50mg">
  <option value="Tab. Bisoprolol 2.5mg">
  <option value="Tab. Metformin 500mg">
  <option value="Tab. Cefuroxime 500mg">
  <option value="Cap. Amoxicillin 500mg">
  <option value="Tab. Azithromycin 500mg">
  <option value="Tab. Neuro-B (Vit B1+B6+B12)">
  <option value="Tab. Calcium 500mg + Vit D3">
</datalist>

<datalist id="doseDatalist">
  <option value="1 tablet">
  <option value="2 tablets">
  <option value="0.5 tablet (অর্ধেক)">
  <option value="1 capsule">
  <option value="1 spoonful (5ml)">
  <option value="2 spoonful (10ml)">
  <option value="1 puff">
  <option value="2 puffs">
  <option value="1 drop">
  <option value="2 drops">
  <option value="1 sachet">
</datalist>

<datalist id="freqDatalist">
  <option value="1+0+1 (সকাল + রাত)">
  <option value="1+1+1 (সকাল + দুপুর + রাত)">
  <option value="0+0+1 (রাতে ১ বার)">
  <option value="1+0+0 (সকালে ১ বার)">
  <option value="0+1+0 (দুপুরে ১ বার)">
  <option value="1+1+0 (সকাল + দুপুর)">
  <option value="0+1+1 (দুপুর + রাত)">
  <option value="1+1+1+1 (৪ বার)">
  <option value="SOS / প্রয়োজন হলে">
</datalist>

<datalist id="durDatalist">
  <option value="3 days">
  <option value="5 days">
  <option value="7 days">
  <option value="10 days">
  <option value="14 days">
  <option value="1 month (১ মাস)">
  <option value="2 months">
  <option value="3 months">
  <option value="Continue (চলবে)">
</datalist>

<datalist id="instDatalist">
  <option value="Before meals (খাবারের ৩০ মি. আগে)">
  <option value="After meals (খাবারের পরে)">
  <option value="With meals (খাবারের সাথে)">
  <option value="At bedtime (রাতে শোবার আগে)">
  <option value="Empty stomach (খালি পেটে)">
  <option value="জ্বর বা তীব্র ব্যথা হলে">
  <option value="কুসুম গরম পানি দিয়ে খাবেন">
</datalist>

<script type="module">
  import { Api, API_BASE } from '../assets/js/api.js';

  const urlParams = new URLSearchParams(window.location.search);
  const appointmentId = parseInt(urlParams.get('appointment_id'), 10);

  if (!appointmentId) {
    alert('No appointment specified.');
    window.location.href = 'queue.php';
  }

  // Pre-fill next month follow-up date
  function setFollowUpDays(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    document.getElementById('followUpDate').value = d.toISOString().split('T')[0];
  }
  setFollowUpDays(30);

  document.querySelectorAll('.btn-quick-date').forEach(btn => {
    btn.addEventListener('click', () => {
      setFollowUpDays(parseInt(btn.dataset.days, 10));
    });
  });

  async function loadAppointmentData() {
    try {
      const res = await Api.get(`/appointments/${appointmentId}`);
      if (res.success) {
        const a = res.data;
        document.getElementById('patientName').innerText = a.patient_name;
        document.getElementById('patientMeta').innerText = (a.gender || 'Patient') + ' • ' + (a.blood_group || 'Blood Group: N/A') + ' • Mobile: ' + (a.patient_phone || 'N/A');
        document.getElementById('patientToken').innerText = a.token_display;
      }
    } catch (err) {
      console.error(err);
    }
  }

  // 1. Symptoms Quick-Select Chips Handler
  document.getElementById('symptomsChips').addEventListener('click', (e) => {
    if (e.target.classList.contains('chip')) {
      const val = e.target.dataset.val;
      const duration = document.getElementById('symptomDurationSelect').value;
      const ta = document.getElementById('symptoms');
      const item = `• ${val} (${duration})`;
      if (!ta.value.includes(val)) {
        ta.value = ta.value ? (ta.value + '\n' + item) : item;
      }
    }
  });

  // 2. Normal Vitals Template & Vitals Chips
  document.getElementById('btnNormalVitals').addEventListener('click', () => {
    const ta = document.getElementById('clinicalNotes');
    ta.value = "BP: 120/80 mmHg\nPulse: 76 bpm (regular)\nTemp: 98.4°F (Afebrile)\nChest: Bilateral clear, vesicular sounds\nHeart: S1 + S2 heard, no murmur\nAbdomen: Soft, non-tender\nEdema: Nil";
  });

  document.getElementById('vitalsChips').addEventListener('click', (e) => {
    if (e.target.classList.contains('chip')) {
      const val = e.target.dataset.val;
      const ta = document.getElementById('clinicalNotes');
      if (!ta.value.includes(val)) {
        ta.value = ta.value ? (ta.value + '\n' + val) : val;
      }
    }
  });

  // 3. Quick Diagnosis Dropdown Handler
  document.getElementById('quickDiagSelect').addEventListener('change', (e) => {
    const val = e.target.value;
    if (val) {
      const [diag, icd] = val.split('|');
      document.getElementById('primaryDiagnosis').value = diag;
      document.getElementById('icdCode').value = icd || '';
    }
  });

  // 4. Quick Add Popular Medicine Dropdown
  document.getElementById('quickMedSelect').addEventListener('change', (e) => {
    const val = e.target.value;
    if (val) {
      const [name, dose, freq, dur, inst] = val.split('|');
      addMedicineRow(name, dose, freq, dur, inst);
      e.target.value = ''; // Reset select
    }
  });

  // 5. Add Dynamic Medicine Row Helper
  function addMedicineRow(name = '', dose = '1 tablet', freq = '1+0+1', dur = '7 days', inst = 'After meals (খাবারের পরে)') {
    const container = document.getElementById('medicinesContainer');
    const row = document.createElement('div');
    row.className = 'med-row';
    row.innerHTML = `
      <input type="text" class="form-control med-name" placeholder="Medicine name" value="${name}" list="commonMedsDatalist" required>
      <input type="text" class="form-control med-dose" placeholder="Dose (1 tab)" value="${dose}" list="doseDatalist">
      <input type="text" class="form-control med-freq" placeholder="Schedule" value="${freq}" list="freqDatalist">
      <input type="text" class="form-control med-dur" placeholder="Duration" value="${dur}" list="durDatalist">
      <input type="text" class="form-control med-inst" placeholder="Instructions" value="${inst}" list="instDatalist">
      <button type="button" class="btn btn-outline btn-sm btn-remove-med" style="color: red; padding: 4px;">&times;</button>
    `;
    container.appendChild(row);
  }

  document.getElementById('btnAddMed').addEventListener('click', () => {
    addMedicineRow();
  });

  document.getElementById('medicinesContainer').addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-remove-med')) {
      const rows = document.querySelectorAll('.med-row');
      if (rows.length > 1) {
        e.target.closest('.med-row').remove();
      }
    }
  });

  // 6. Investigations Chips Handler
  document.getElementById('investigationChips').addEventListener('click', (e) => {
    if (e.target.classList.contains('chip')) {
      const val = e.target.dataset.val;
      const ta = document.getElementById('investigations');
      if (!ta.value.includes(val)) {
        ta.value = ta.value ? (ta.value + '\n' + val) : val;
      }
    }
  });

  // 7. Advice Chips Handler
  document.getElementById('adviceChips').addEventListener('click', (e) => {
    if (e.target.classList.contains('chip')) {
      const val = e.target.dataset.val;
      const ta = document.getElementById('advice');
      if (!ta.value.includes(val)) {
        ta.value = ta.value ? (ta.value + '\n' + val) : val;
      }
    }
  });

  // Form Submit Handler
  document.getElementById('consultationForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveRx');
    btn.disabled = true;
    btn.innerText = 'Saving & Signing Prescription...';

    // Collect medicines
    const medicines = [];
    document.querySelectorAll('.med-row').forEach(row => {
      const name = row.querySelector('.med-name').value.trim();
      if (name) {
        medicines.push({
          medicine_name: name,
          dosage: row.querySelector('.med-dose').value.trim(),
          frequency: row.querySelector('.med-freq').value.trim(),
          duration: row.querySelector('.med-dur').value.trim(),
          instructions: row.querySelector('.med-inst').value.trim()
        });
      }
    });

    // Collect investigations
    const invRaw = document.getElementById('investigations').value.trim();
    const investigations = invRaw ? invRaw.split('\n').map(t => ({ test_name: t.trim() })).filter(t => t.test_name) : [];

    // Collect advice
    const advRaw = document.getElementById('advice').value.trim();
    const advice = advRaw ? advRaw.split('\n').map(a => ({ advice_text: a.trim() })).filter(a => a.advice_text) : [];

    const payload = {
      appointment_id: appointmentId,
      symptoms: document.getElementById('symptoms').value.trim(),
      clinical_notes: document.getElementById('clinicalNotes').value.trim(),
      follow_up_date: document.getElementById('followUpDate').value,
      diagnoses: [
        {
          condition_name: document.getElementById('primaryDiagnosis').value.trim(),
          icd10_code: document.getElementById('icdCode').value.trim() || null,
          severity: 'MODERATE',
          type: 'PRIMARY'
        }
      ],
      medicines,
      investigations,
      advice
    };

    try {
      // 1. Save Consultation & Prescription
      const res = await Api.post('/consultations', payload);
      if (res.success) {
        // 2. Complete Consultation in Queue
        try {
          await Api.post('/queue/doctor/complete', { appointment_id: appointmentId });
        } catch (queueErr) {
          console.warn('Queue advance note:', queueErr.message);
        }

        alert(`Prescription #${res.data.prescription_code} issued successfully! Opening printable view...`);
        // Open PDF printable dynamically using API_BASE
        window.open(`${API_BASE}/prescriptions/${res.data.prescription_id}/pdf`, '_blank');
        window.location.href = 'queue.php';
      }
    } catch (err) {
      alert(err.message || 'Failed to save prescription');
    } finally {
      btn.disabled = false;
      btn.innerText = '✓ Save & Issue Digital Prescription';
    }
  });

  document.getElementById('btnPaperRxComplete').addEventListener('click', async () => {
    if (!appointmentId) return;
    if (!confirm('Complete consultation with handwritten paper prescription? (প্রেসক্রিপশন ছাড়া ভিজিট সম্পন্ন করবেন?)')) {
      return;
    }
    try {
      await Api.post('/queue/doctor/complete', { appointment_id: appointmentId });
      alert('Consultation completed successfully! Patient will receive notification.');
      window.location.href = 'queue.php';
    } catch (err) {
      alert(err.message || 'Failed to complete visit');
    }
  });

  loadAppointmentData();
</script>

</body>
</html>
