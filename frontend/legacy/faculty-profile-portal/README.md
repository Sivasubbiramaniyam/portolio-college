# UNIVERSITY COLLEGE OF ENGINEERING NAGERCOIL (UCEN)
### (A Constituent College of Anna University, Chennai)
## Faculty Profile Creation and Management Portal

A professional, modern, responsive academic faculty profile creation and management portal developed strictly with **HTML5, CSS3, and Vanilla JavaScript** (Zero frameworks, zero dependencies, no React/JSX, no Tailwind CSS, no Bootstrap).

---

## 🏛️ Project Overview

The **UCEN Faculty Profile Management Portal** allows faculty members to enter, maintain, and publish their comprehensive academic profile across **13 standardised parameters** aligned with Anna University guidelines, AICTE mandatory disclosures, and NBA/NAAC accreditation metrics. Upon submitting the form, the system automatically generates an academic CV with formal print and PDF export capabilities.

---

## 🎨 Design Theme & Branding

- **Primary Brand Colour**: `#7B5EF0` (RGB: `rgb(123, 94, 240)`, HSL: `hsl(252°, 84%, 65%)`)
- **Background**: Light lavender (`#f8f7ff`, `#fbf9ff`) with clean white form containers
- **Typography**: Google Fonts **Poppins** (headings) & **Inter** (body)
- **Official Emblem**: High-quality authentic Anna University vector emblem
- **Aesthetic**: University-level formal appearance, subtle shadows (`0 4px 14px rgba(123, 94, 240, 0.08)`), rounded cards (10px–20px), responsive across desktop, tablet, and mobile.

---

## 📁 File Structure

```
faculty-profile-portal/
│
├── index.html                  # Semantic HTML5 single-page application structure
├── style.css                   # Pure Vanilla CSS3 design system & print styles
├── script.js                   # Pure Vanilla JavaScript application logic & storage
│
├── assets/
│   ├── anna-university-logo.svg # Official authentic Anna University crest
│   ├── anna-university-logo.png # Transparent PNG copy
│   ├── default-profile.svg     # Clean default avatar
│   └── default-profile.png     # Default profile placeholder
│
└── README.md                   # Complete documentation & usage guide
```

---

## 📑 13 Academic Parameters (In Exact Order)

1. **Personal Information**: Full Name, Designation, Department, Employee ID, Email, Contact, Date of Joining, DOB, Biography, Areas of Specialisation.
2. **Educational Qualifications**: Degree, Specialisation, Institution, University, Year of Passing, CGPA/%, Status (*Dynamic Add/Remove*).
3. **Academic & Professional Experience**: Institution, Designation, Experience Type, From/To Dates, Duration, Key Responsibilities (*Dynamic Add/Remove*).
4. **Membership of Professional Institutions**: IEEE, ISTE, ACM, CSI, IEI, Category, ID, Type, Year (*Dynamic Add/Remove*).
5. **Positions Held**: Position, Organisation, Department, Dates, Responsibilities (*Dynamic Add/Remove*).
6. **Key Achievements & Patents**: Title, Category, Organisation, Year, Certificate (*Dynamic Add/Remove*).
7. **Consultancy & Testing Work**: Title, Client, Role, Duration, Deliverables, Revenue Outcome (*Dynamic Add/Remove*).
8. **Journal Publications**: Title, Authors, Journal, Volume, Issue, Year, DOI, Indexing (*Scopus, Web of Science, UGC CARE*) (*Dynamic Add/Remove*).
9. **Book / Book Chapters**: Title, Authors, Publisher, ISBN, Year, Pages (*Dynamic Add/Remove*).
10. **Invited Speaker & Lectures**: Talk Title, Event, Institution, Role, Date, Venue (*Dynamic Add/Remove*).
11. **Certifications**: NPTEL, Coursera, AWS, IIT, Specialisation, Completion Date, File (*Dynamic Add/Remove*).
12. **FDP / Workshops Attended**: Programme Name, Type, Institution, Duration, Dates (*Dynamic Add/Remove*).
13. **Funded Projects & Research Grants (VISUALLY HIGHLIGHTED PARAMETER)**:
    - Project Title
    - Principal Investigator (PI) & Co-PI
    - Funding Agency (*DST, SERB, AICTE, DRDO, TNSCST*)
    - Project Category & Duration
    - Sanctioned Amount (₹)
    - Grant / Sanction Order Number
    - Status (*Proposed, Approved, Ongoing, Completed*)
    - Research Outcomes & Publications Generated
    - Sanction Letter PDF Attachment
    - *Add Another Project & Remove Project buttons*

---

## ⚡ Key Functionalities

- **Circular Profile Photo Upload**: Real-time image upload with `FileReader`, instant preview, sample avatar switcher, and remove options.
- **Accordion Navigation**: Expand and collapse individual or all 13 sections with active indicators.
- **Dynamic Multi-Entry Rows**: Add and remove repeat entries for qualifications, publications, grants, and experience.
- **Profile Completion Percentage**: Dynamically computed progress indicator (0% to 100%) tracking completed parameters.
- **Automated Academic CV Generation**: Renders full publication-ready faculty curriculum vitae with one click.
- **Print & PDF Export**: Native `@media print` CSS cleanly strips navigation and formats the CV for physical printing or saving as PDF.
- **Draft Persistence**: Uses browser `localStorage` for offline persistence across reloads.
- **Faculty Mode**: Register, login, edit, preview, and publish your personal academic portfolio.
- **One-Click Evaluator Testing**: Instant demo buttons for Dr. K. Ramanathan (CSE HOD), Dr. M. Anitha (ECE), and Dr. G. Priya (MBA).

---

## 🚀 How to Run

Because this project is built entirely with **Vanilla HTML5, CSS3, and JavaScript**, no installation, build process, or server configuration is required:

1. **Option A (Direct in Browser)**:
   - Double-click `index.html` to open directly in Google Chrome, Microsoft Edge, Mozilla Firefox, or Safari.

2. **Option B (Using any lightweight local server)**:
   ```bash
   # Python 3
   python -m http.server 8000

   # Or Node npx serve
   npx serve .
   ```
   Open `http://localhost:8000/` in your browser.

---

## 🏛️ Academic Departments (8)

1. **Department of Computer Science and Engineering (CSE)**
2. **Department of Information Technology (IT)**
3. **Department of Electronics and Communication Engineering (ECE)**
4. **Department of Electrical and Electronics Engineering (EEE)**
5. **Department of Mechanical Engineering (MECH)**
6. **Department of Civil Engineering (CIVIL)**
7. **Department of Management Studies (MBA)**
8. **Department of Science and Humanities (S&H)**

---

## 🏛️ Statutory Details

- **Institution**: University College of Engineering Nagercoil (UCEN)
- **Affiliation**: Constituent College of Anna University, Chennai
- **Campus Address**: Industrial Estate, Konam, Nagercoil, Kanyakumari District, Tamil Nadu – 629 004
- **DOTE Code**: 4999

