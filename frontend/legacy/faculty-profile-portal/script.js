/**
 * UNIVERSITY COLLEGE OF ENGINEERING NAGERCOIL (UCEN)
 * (A Constituent College of Anna University, Chennai)
 * Faculty Profile Management Portal
 * Pure Vanilla JavaScript Application Logic
 */

// =============================================================================
// 1. INITIAL REALISTIC FACULTY DATASETS
// =============================================================================
const SAMPLE_FACULTY_DR_RAMANATHAN = {
  id: "ucen-cse-1042",
  status: "Published",
  personalInfo: {
    fullName: "Dr. K. Ramanathan",
    designation: "Professor & Head of Department",
    department: "Computer Science and Engineering",
    employeeId: "UCEN-CSE-1042",
    email: "ramanathan.k@ucen.ac.in",
    contactNumber: "+91 94432 18765",
    doj: "2010-07-15",
    dob: "1978-05-14",
    photo: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400",
    areasOfSpecialisation: [
      "Artificial Intelligence & Machine Learning",
      "Cloud Computing Security",
      "Distributed Systems",
      "Deep Learning for Medical Informatics"
    ],
    biography: "Dr. K. Ramanathan has over 18 years of academic, research, and administrative experience in Computer Science and Engineering. He received his Ph.D. from Anna University, Chennai in 2012 for his work on Fault-Tolerant Distributed Cloud Computing Systems. He has published over 38 research papers in indexed journals and served as the Principal Investigator for research projects sanctioned by DST and AICTE. He is actively involved in NBA accreditation initiatives and curriculum development under Anna University."
  },
  qualifications: [
    {
      degree: "Ph.D.",
      specialisation: "Computer Science and Engineering (Distributed Systems)",
      institution: "College of Engineering, Guindy (CEG)",
      university: "Anna University, Chennai",
      yearOfPassing: "2012",
      cgpa: "Commended",
      status: "Completed"
    },
    {
      degree: "M.E.",
      specialisation: "Computer Science and Engineering",
      institution: "Government College of Technology, Coimbatore",
      university: "Anna University",
      yearOfPassing: "2005",
      cgpa: "8.9 / 10 (First Class with Distinction)",
      status: "Completed"
    },
    {
      degree: "B.E.",
      specialisation: "Computer Science and Engineering",
      institution: "Government College of Engineering, Tirunelveli",
      university: "Manonmaniam Sundaranar University",
      yearOfPassing: "2001",
      cgpa: "82.4%",
      status: "Completed"
    }
  ],
  experience: [
    {
      institution: "University College of Engineering Nagercoil (UCEN)",
      designation: "Professor & Head of Department",
      experienceType: "Academic Experience",
      fromDate: "2018-06-01",
      toDate: "Present",
      totalExperience: "6+ Years",
      responsibilities: "Overall departmental administration, academic planning, mentoring doctoral scholars, overseeing NBA & NAAC criteria, and Anna University zonal evaluations."
    },
    {
      institution: "University College of Engineering Nagercoil (UCEN)",
      designation: "Associate Professor",
      experienceType: "Teaching Experience",
      fromDate: "2013-07-01",
      toDate: "2018-05-31",
      totalExperience: "5 Years",
      responsibilities: "Instruction in Advanced Algorithms, Cloud Security; guided 14 M.E. dissertations and coordinated university symposia."
    },
    {
      institution: "Cognizant Technology Solutions (CTS), Chennai",
      designation: "Software Engineer",
      experienceType: "Industrial Experience",
      fromDate: "2005-07-01",
      toDate: "2007-12-15",
      totalExperience: "2.5 Years",
      responsibilities: "Enterprise database replication and multi-threaded transaction middleware optimization."
    }
  ],
  memberships: [
    {
      institutionName: "Institute of Electrical and Electronics Engineers (IEEE)",
      membershipCategory: "Senior Member",
      membershipId: "IEEE-SM-9382104",
      membershipType: "Life Member",
      yearOfJoining: "2014"
    },
    {
      institutionName: "Indian Society for Technical Education (ISTE)",
      membershipCategory: "Life Member",
      membershipId: "LM-88349",
      membershipType: "Life Member",
      yearOfJoining: "2007"
    },
    {
      institutionName: "Computer Society of India (CSI)",
      membershipCategory: "Professional Member",
      membershipId: "CSI-TN-04921",
      membershipType: "Life Member",
      yearOfJoining: "2011"
    }
  ],
  positionsHeld: [
    {
      position: "Head of the Department (HOD)",
      organisationName: "University College of Engineering Nagercoil",
      department: "Computer Science and Engineering",
      fromDate: "2018-06-01",
      toDate: "Present",
      responsibilities: "Supervising academic curriculum delivery, high performance lab development, and faculty research mentorship."
    },
    {
      position: "Chief Superintendent / Zonal Officer",
      organisationName: "Anna University Zone-XX",
      department: "Examination Cell",
      fromDate: "2021-08-01",
      toDate: "2023-07-31",
      responsibilities: "Conducted Anna University theory and practical semester examinations across affiliated engineering institutions."
    }
  ],
  achievements: [
    {
      title: "Tamil Nadu State Council for Science and Technology (TNSCST) Young Scientist Award",
      description: "Recognized for contributions in medical informatics and diagnostic machine learning algorithms.",
      organisation: "Department of Higher Education, Govt. of Tamil Nadu",
      category: "State Award",
      year: "2020",
      document: "TNSCST_Young_Scientist_2020.pdf"
    },
    {
      title: "Patent Granted: IoT-Enabled Real-Time Cardiac Arrhythmia Detection System",
      description: "Indian Patent No. 412891 granted for an edge-computing wearable device with lightweight neural inferencing.",
      organisation: "Indian Patent Office, Govt. of India",
      category: "Patent Granted",
      year: "2022",
      document: "Patent_Grant_412891.pdf"
    }
  ],
  consultancy: [
    {
      projectTitle: "AI-Powered Automated Defect Inspection for Natural Fibre Manufacturing",
      client: "Kanyakumari Coir Industry Consortium",
      projectDescription: "Developed edge computer vision models for high-speed flaw detection in exported coir composites.",
      role: "Principal Consultant",
      duration: "14 Months (2022 - 2023)",
      outcome: "Improved quality assurance to 98.4% and generated ₹4.5 Lakhs revenue for UCEN testing account."
    }
  ],
  journalPublications: [
    {
      title: "A Lightweight Deep Convolutional Neural Network for Early Screening of Diabetic Retinopathy on Edge Devices",
      authors: "K. Ramanathan, S. Meenakshi, and V. Sundaram",
      journal: "IEEE Transactions on Biomedical Engineering",
      volume: "70",
      issue: "4",
      publicationYear: "2023",
      doi: "10.1109/TBME.2023.3241902",
      indexing: "Scopus & Web of Science (SCI-E, Impact Factor: 4.6)"
    },
    {
      title: "Dynamic Resource Provisioning and SLA-Aware Load Balancing in Hybrid Multi-Cloud Computing Environments",
      authors: "K. Ramanathan, R. Rajasekar, and M. Anitha",
      journal: "Elsevier Journal of Systems and Software",
      volume: "189",
      issue: "2",
      publicationYear: "2022",
      doi: "10.1016/j.jss.2022.111298",
      indexing: "Scopus & Web of Science (SCI, Impact Factor: 3.8)"
    },
    {
      title: "Blockchain-Assisted Federated Learning Framework for Privacy-Preserving Internet of Medical Things",
      authors: "K. Ramanathan and T. Jayakumar",
      journal: "Springer Computing",
      volume: "104",
      issue: "8",
      publicationYear: "2022",
      doi: "10.1007/s00607-022-01089-1",
      indexing: "Scopus & Web of Science (SCI-E)"
    }
  ],
  books: [
    {
      title: "Modern Cloud Computing Architecture and Security Protocols",
      authors: "Dr. K. Ramanathan & Dr. M. Anitha",
      publisher: "Springer Nature Singapore",
      isbn: "978-981-19-4821-3",
      publicationYear: "2023",
      pageNumbers: "348 Pages"
    }
  ],
  invitedTalks: [
    {
      title: "Keynote Address: Generative AI and Ethical Challenges in Engineering Education",
      event: "AICTE Sponsored International Conference on Advanced Computing (ICAC)",
      institution: "National Institute of Technology (NIT), Tiruchirappalli",
      role: "Keynote Speaker",
      date: "2024-02-18",
      venue: "Auditorium, NIT Trichy"
    }
  ],
  certifications: [
    {
      name: "Deep Learning Specialization (5-Course Series)",
      organisation: "DeepLearning.AI / Coursera",
      specialisation: "CNNs, RNNs & Transformers",
      completionDate: "2022-09-15",
      url: "https://coursera.org/verify/DLAI-99841",
      certificate: "DeepLearning_Specialization.pdf"
    },
    {
      name: "Cloud Computing (12-Week Faculty Elite+Gold Certificate)",
      organisation: "NPTEL - IIT Kharagpur",
      specialisation: "Virtualization & Cloud Infrastructure",
      completionDate: "2021-11-20",
      url: "https://nptel.ac.in",
      certificate: "NPTEL_Elite_Gold_Cloud.pdf"
    }
  ],
  fdpAttended: [
    {
      programmeName: "Two-Week ATAL FDP on Quantum Computing and High-Performance Cryptography",
      programmeType: "FDP",
      institution: "Indian Institute of Science (IISc), Bengaluru",
      duration: "14 Days",
      startDate: "2023-07-10",
      endDate: "2023-07-23",
      certificate: "IISc_ATAL_Quantum_2023.pdf"
    }
  ],
  // 13. FUNDED PROJECTS & RESEARCH GRANTS (HIGHLIGHTED)
  fundedProjects: [
    {
      projectTitle: "Development of an AI-Driven Point-of-Care Diagnostic Framework for Early Detection of Oral Cancers in Rural Primary Health Centres",
      pi: "Dr. K. Ramanathan (Professor & HOD, CSE, UCEN)",
      coPi: "Dr. M. Anitha (Associate Professor, ECE, UCEN) & Dr. V. Sundaram (Kanyakumari Govt. Medical College)",
      fundingAgency: "Department of Science and Technology (DST) - Biomedical Device Division, Govt. of India",
      projectCategory: "Applied R&D & Clinical Translation",
      projectDescription: "This grant funds the fabrication of an ultra-portable multispectral oral imaging camera equipped with on-chip edge neural inferencing for primary health centres (PHCs) in southern Tamil Nadu.",
      sanctionedAmount: "₹ 48,50,000",
      startDate: "2022-04-01",
      endDate: "2025-03-31",
      duration: "36 Months (3 Years)",
      status: "Ongoing",
      sanctionOrderNumber: "DST/TDT/BDD/2022/194-G",
      researchOutcomes: "2 Patents filed (Application Nos. 202341029182 & 202341058291), 3 Clinical prototype units deployed across 4 PHCs in Kanyakumari district, 1 Doctoral scholar fully supported.",
      publicationsGenerated: "3 SCI Journal Papers (IEEE TBME 2023, Elsevier JSS 2022, Springer Computing 2022).",
      sanctionLetter: "DST_Sanction_Order_48Lakhs_UCEN.pdf"
    },
    {
      projectTitle: "Establishment of Advanced Cloud and Edge Computing Laboratory under Modernisation and Removal of Obsolescence (MODROBS)",
      pi: "Dr. K. Ramanathan (UCEN)",
      coPi: "Er. T. Senthil Kumar (Assistant Professor, CSE)",
      fundingAgency: "All India Council for Technical Education (AICTE), New Delhi",
      projectCategory: "Infrastructure & Modernisation",
      projectDescription: "Upgradation of high-performance GPU server infrastructure (NVIDIA A100 nodes) for supporting deep learning research for engineering students.",
      sanctionedAmount: "₹ 18,25,000",
      startDate: "2020-09-15",
      endDate: "2022-09-14",
      duration: "24 Months",
      status: "Completed",
      sanctionOrderNumber: "AICTE/MODROBS/POLICY-I/2020-21/981",
      researchOutcomes: "Established state-of-the-art UCEN High Performance Computing Lab with 60 workstations. Over 450 students trained.",
      publicationsGenerated: "4 M.E. Student Research Publications in IEEE Xplore.",
      sanctionLetter: "AICTE_MODROBS_UCEN_Sanction.pdf"
    },
    {
      projectTitle: "IoT-Based Saltpan Automation and Evaporation Rate Monitoring System for Kanyakumari Coastal Zones",
      pi: "Dr. K. Ramanathan",
      coPi: "Dr. S. Vijayakumar (Assistant Professor, EEE)",
      fundingAgency: "Tamil Nadu State Council for Science and Technology (TNSCST)",
      projectCategory: "State Priority Sector Project",
      projectDescription: "Deploying solar-powered wireless sensor networks (WSN) to monitor brine density, water level, and environmental parameters in coastal salt production pans.",
      sanctionedAmount: "₹ 5,00,000",
      startDate: "2023-01-01",
      endDate: "2024-06-30",
      duration: "18 Months",
      status: "Completed",
      sanctionOrderNumber: "TNSCST/STP/PRG/2022/411",
      researchOutcomes: "Transfer of technology (ToT) demo completed with local Salt Manufacturers Association; yield forecasting algorithm achieved 92% efficiency.",
      publicationsGenerated: "1 UGC-CARE List-I Journal Publication.",
      sanctionLetter: "TNSCST_Saltpan_Sanction.pdf"
    }
  ]
};

const SAMPLE_FACULTY_DR_ANITHA = {
  id: "ucen-ece-2018",
  status: "Published",
  personalInfo: {
    fullName: "Dr. M. Anitha",
    designation: "Associate Professor",
    department: "Electronics and Communication Engineering",
    employeeId: "UCEN-ECE-2018",
    email: "anitha.m@ucen.ac.in",
    contactNumber: "+91 94871 54320",
    doj: "2012-08-01",
    dob: "1983-11-22",
    photo: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=400",
    areasOfSpecialisation: [
      "VLSI Design & Embedded Systems",
      "Wireless Sensor Networks",
      "Biomedical Signal Processing",
      "FPGA Architecture"
    ],
    biography: "Dr. M. Anitha is an Associate Professor in the Department of Electronics and Communication Engineering at UCEN. She completed her Ph.D. from Anna University in 2016 specializing in Low-Power VLSI Architectures for Biomedical DSP. She has authored over 25 peer-reviewed publications and mentors student teams for national hackathons."
  },
  qualifications: [
    {
      degree: "Ph.D.",
      specialisation: "Information and Communication Engineering (Low-Power VLSI)",
      institution: "College of Engineering, Guindy",
      university: "Anna University, Chennai",
      yearOfPassing: "2016",
      cgpa: "Highly Commended",
      status: "Completed"
    },
    {
      degree: "M.E.",
      specialisation: "VLSI Design",
      institution: "PSG College of Technology, Coimbatore",
      university: "Anna University",
      yearOfPassing: "2008",
      cgpa: "9.1 / 10 (University Gold Medalist)",
      status: "Completed"
    }
  ],
  experience: [
    {
      institution: "University College of Engineering Nagercoil (UCEN)",
      designation: "Associate Professor",
      experienceType: "Academic Experience",
      fromDate: "2019-07-01",
      toDate: "Present",
      totalExperience: "5+ Years",
      responsibilities: "UG & PG teaching in VLSI Systems, RF Design, lab development for Cadence EDA suite, and research supervisor for Ph.D. scholars."
    }
  ],
  memberships: [
    {
      institutionName: "Institute of Electrical and Electronics Engineers (IEEE)",
      membershipCategory: "Senior Member",
      membershipId: "IEEE-9182371",
      membershipType: "Annual Member",
      yearOfJoining: "2013"
    }
  ],
  positionsHeld: [
    {
      position: "Faculty Advisor, IEEE WIE Affinity Group",
      organisationName: "UCEN Student Branch",
      department: "ECE",
      fromDate: "2019-01-01",
      toDate: "Present",
      responsibilities: "Guiding women engineering students in hackathons, coding contests, and outreach programs."
    }
  ],
  achievements: [
    {
      title: "Best Faculty Mentor - Smart India Hackathon (SIH)",
      description: "Mentored student team who won 1st Prize of ₹1 Lakh in SIH Hardware Edition.",
      organisation: "Ministry of Education (MoE), Govt. of India",
      category: "National Recognition",
      year: "2022",
      document: "SIH_Best_Mentor_2022.pdf"
    }
  ],
  consultancy: [
    {
      projectTitle: "Low-Power Sub-GHz RF Transceiver Module for Marine Fishermen Tracking",
      client: "Coastal Fishery Development Directorate",
      projectDescription: "Design and testing of emergency long-range beacon transmitting distress signals up to 35 nautical miles.",
      role: "Technical Lead",
      duration: "12 Months (2023)",
      outcome: "Delivered 20 working prototype transceivers and generated ₹3.8 Lakhs consultancy receipt."
    }
  ],
  journalPublications: [
    {
      title: "Design of Low-Power Approximate Multipliers for High-Throughput Biomedical Image Enhancement",
      authors: "M. Anitha, S. Lakshmi, and R. Swaminathan",
      journal: "IEEE Transactions on Very Large Scale Integration (VLSI) Systems",
      volume: "31",
      issue: "3",
      publicationYear: "2023",
      doi: "10.1109/TVLSI.2023.3239841",
      indexing: "Scopus & SCI (Impact Factor: 2.8)"
    }
  ],
  books: [
    {
      title: "Low-Power Digital VLSI Design: Principles and SystemVerilog Implementations",
      authors: "Dr. M. Anitha",
      publisher: "Prentice Hall of India (PHI Learning)",
      isbn: "978-81-203-5321-8",
      publicationYear: "2021",
      pageNumbers: "280 Pages"
    }
  ],
  invitedTalks: [
    {
      title: "Emerging Trends in Neuromorphic Computing & VLSI Architectures",
      event: "National Conference on Innovations in Electronics",
      institution: "Thiagarajar College of Engineering, Madurai",
      role: "Invited Speaker",
      date: "2023-09-14",
      venue: "ECE Seminar Hall, TCE Madurai"
    }
  ],
  certifications: [
    {
      name: "VLSI Physical Design & Timing Closure",
      organisation: "Synopsys University Program",
      specialisation: "Place & Route, STA using Primetime",
      completionDate: "2021-08-20",
      url: "https://synopsys.com",
      certificate: "Synopsys_Physical_Design.pdf"
    }
  ],
  fdpAttended: [
    {
      programmeName: "One-Week AICTE Training and Learning (ATAL) Academy FDP on System-on-Chip (SoC) Design",
      programmeType: "FDP",
      institution: "IIT Madras",
      duration: "5 Days",
      startDate: "2022-11-14",
      endDate: "2022-11-18",
      certificate: "IITM_ATAL_SoC.pdf"
    }
  ],
  fundedProjects: [
    {
      projectTitle: "Design and Silicon Prototyping of an Ultra-Low-Power Edge Processor for Implantable Cardiac Pacemakers",
      pi: "Dr. M. Anitha (Associate Professor, ECE, UCEN)",
      coPi: "Dr. S. Vijayakumar (Assistant Professor, EEE, UCEN)",
      fundingAgency: "Science and Engineering Research Board (SERB) - Core Research Grant (CRG)",
      projectCategory: "Core Engineering Research",
      projectDescription: "Developing sub-threshold CMOS voltage circuit architecture that achieves 65% battery life extension for implantable medical pacemakers using 28nm FD-SOI technology.",
      sanctionedAmount: "₹ 34,75,000",
      startDate: "2023-03-01",
      endDate: "2026-02-28",
      duration: "36 Months",
      status: "Ongoing",
      sanctionOrderNumber: "SERB/CRG/2022/004812",
      researchOutcomes: "Fabricated ASIC test chip submitted to SCL Chandigarh foundry for MPW run; 1 Research Associate appointed; 2 Scopus indexed articles published.",
      publicationsGenerated: "IEEE Transactions on VLSI Systems (2023) and IEEE ISCAS Conference Paper.",
      sanctionLetter: "SERB_CRG_Anitha_SanctionOrder.pdf"
    }
  ]
};

const SAMPLE_FACULTY_DR_SATHISH = {
  id: "ucen-it-7012",
  status: "Published",
  personalInfo: {
    fullName: "Dr. R. Sathish Kumar",
    designation: "Associate Professor & Head of Department",
    department: "Information Technology",
    employeeId: "UCEN-IT-7012",
    email: "sathishkumar.r@ucen.ac.in",
    contactNumber: "+91 94421 88762",
    doj: "2013-06-20",
    dob: "1981-08-14",
    photo: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=400",
    areasOfSpecialisation: [
      "Cyber-Physical Systems & Network Forensics",
      "Cloud Security & Data Privacy",
      "Big Data Analytics & IoT",
      "Cryptographic Protocols"
    ],
    biography: "Dr. R. Sathish Kumar leads the Department of Information Technology at UCEN. He earned his Ph.D. in Information and Communication Engineering from Anna University in 2015. He has authored over 22 papers in Scopus-indexed journals and serves as the Principal Investigator for an AICTE RPS grant. He coordinates the UCEN Cyber Security and Cloud Infrastructure Lab."
  },
  qualifications: [
    {
      degree: "Ph.D.",
      specialisation: "Information & Communication Engineering (Network Forensics)",
      institution: "College of Engineering, Guindy",
      university: "Anna University, Chennai",
      yearOfPassing: "2015",
      cgpa: "Commended",
      status: "Completed"
    },
    {
      degree: "M.Tech.",
      specialisation: "Information Technology",
      institution: "Madras Institute of Technology (MIT Campus)",
      university: "Anna University",
      yearOfPassing: "2007",
      cgpa: "8.8 / 10",
      status: "Completed"
    }
  ],
  experience: [
    {
      institution: "University College of Engineering Nagercoil (UCEN)",
      designation: "Associate Professor & HOD",
      experienceType: "Academic Experience",
      fromDate: "2019-06-01",
      toDate: "Present",
      totalExperience: "5+ Years",
      responsibilities: "Head of Information Technology Department, curriculum implementation for Regulation 2021, and student placement training coordinator."
    }
  ],
  memberships: [
    {
      institutionName: "Institute of Electrical and Electronics Engineers (IEEE)",
      membershipCategory: "Senior Member",
      membershipId: "IEEE-9348120",
      membershipType: "Life Member",
      yearOfJoining: "2015"
    },
    {
      institutionName: "Computer Society of India (CSI)",
      membershipCategory: "Life Member",
      membershipId: "CSI-LM-8921",
      membershipType: "Life Member",
      yearOfJoining: "2010"
    }
  ],
  positionsHeld: [
    {
      position: "Head of the Department (HOD)",
      organisationName: "UCEN Nagercoil",
      department: "Information Technology",
      fromDate: "2019-06-01",
      toDate: "Present",
      responsibilities: "Supervision of IT laboratories, faculty research reviews, and Anna University syllabus revision."
    }
  ],
  achievements: [
    {
      title: "Excellence in Technical Education Award",
      description: "Conferred for establishing the UCEN Cyber Security and Threat Analysis Testbed.",
      organisation: "Anna University, Chennai",
      category: "Teaching Excellence",
      year: "2023",
      document: "Excellence_Award_2023.pdf"
    }
  ],
  consultancy: [
    {
      projectTitle: "Penetration Testing and Cyber Security Vulnerability Assessment",
      client: "Southern District Industrial Cooperative Bank",
      projectDescription: "ISO 27001 vulnerability audit and web application penetration testing for financial portal.",
      role: "Lead Security Consultant",
      duration: "8 Months (2023)",
      outcome: "Secured core banking interfaces and generated ₹3.5 Lakhs institutional fee."
    }
  ],
  journalPublications: [
    {
      title: "Blockchain-Empowered Privacy-Preserving Framework for Decentralised Healthcare Data Exchange",
      authors: "R. Sathish Kumar, K. Ramanathan, and P. Selvam",
      journal: "IEEE Systems Journal",
      volume: "17",
      issue: "2",
      publicationYear: "2023",
      doi: "10.1109/JSYST.2022.3214567",
      indexing: "Scopus & SCI (Impact Factor: 4.4)"
    }
  ],
  books: [
    {
      title: "Modern Network Forensics and Incident Response Protocols",
      authors: "Dr. R. Sathish Kumar",
      publisher: "CRC Press (Taylor & Francis)",
      isbn: "978-036-74-8921-5",
      publicationYear: "2022",
      pageNumbers: "310 Pages"
    }
  ],
  invitedTalks: [
    {
      title: "Zero-Trust Architecture in Industrial Cloud Ecosystems",
      event: "International Conference on Cyber Security (ICCS)",
      institution: "National Institute of Technology (NIT), Calicut",
      role: "Keynote Speaker",
      date: "2024-01-20",
      venue: "Auditorium, NIT Calicut"
    }
  ],
  certifications: [
    {
      name: "Certified Information Systems Security Professional (CISSP)",
      organisation: "(ISC)²",
      specialisation: "Information Security & Risk Management",
      completionDate: "2022-05-18",
      url: "https://isc2.org/verify",
      certificate: "CISSP_Certificate.pdf"
    }
  ],
  fdpAttended: [
    {
      programmeName: "ATAL FDP on Cloud Security and Blockchain Technologies",
      programmeType: "FDP",
      institution: "IIT Hyderabad",
      duration: "5 Days",
      startDate: "2023-09-11",
      endDate: "2023-09-15",
      certificate: "IITH_ATAL_Security.pdf"
    }
  ],
  fundedProjects: [
    {
      projectTitle: "Design and Deployment of an AI-Driven Zero-Trust Security Framework for Edge-IoT Industrial Automation",
      pi: "Dr. R. Sathish Kumar (Associate Professor & HOD, IT, UCEN)",
      coPi: "Dr. K. Ramanathan (Professor & HOD, CSE, UCEN)",
      fundingAgency: "AICTE Research Promotion Scheme (RPS)",
      projectCategory: "Applied Cyber Security R&D",
      projectDescription: "Developing anomaly detection and mutual authentication protocols for low-latency IoT gateways deployed in manufacturing plants.",
      sanctionedAmount: "₹ 19,50,000",
      startDate: "2023-01-01",
      endDate: "2025-12-31",
      duration: "36 Months",
      status: "Ongoing",
      sanctionOrderNumber: "AICTE/RPS/IT/2022-23/819",
      researchOutcomes: "2 Scopus journal papers published; patent filed for lightweight cryptographic handshake.",
      publicationsGenerated: "IEEE Systems Journal (2023) and IEEE Internet of Things Journal.",
      sanctionLetter: "AICTE_RPS_Sathish_Sanction.pdf"
    }
  ]
};

const SAMPLE_FACULTY_DR_PRIYA = {
  id: "ucen-mba-8004",
  status: "Published",
  personalInfo: {
    fullName: "Dr. G. Priya",
    designation: "Associate Professor & Head of Department",
    department: "MBA",
    employeeId: "UCEN-MBA-8004",
    email: "priya.g@ucen.ac.in",
    contactNumber: "+91 94881 23419",
    doj: "2014-07-10",
    dob: "1982-03-25",
    photo: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=400",
    areasOfSpecialisation: [
      "Financial Analytics & FinTech",
      "MSME Development & Rural Entrepreneurship",
      "Strategic Management & Digital Marketing",
      "Human Resource Analytics"
    ],
    biography: "Dr. G. Priya leads the Department of Management Studies (MBA) at UCEN. She holds a Ph.D. in Management Sciences from Anna University (2014). With over 15 years of postgraduate management instruction, she has published 18 research papers in indexed journals and spearheaded incubation initiatives connecting student entrepreneurs with MSME financing."
  },
  qualifications: [
    {
      degree: "Ph.D.",
      specialisation: "Management Studies (Financial Analytics)",
      institution: "Department of Management Studies (DOMS)",
      university: "Anna University, Chennai",
      yearOfPassing: "2014",
      cgpa: "Highly Commended",
      status: "Completed"
    },
    {
      degree: "M.B.A.",
      specialisation: "Finance and Human Resource Management",
      institution: "PSG Institute of Management, Coimbatore",
      university: "Bharathiar University",
      yearOfPassing: "2006",
      cgpa: "8.7 / 10 (First Rank)",
      status: "Completed"
    }
  ],
  experience: [
    {
      institution: "University College of Engineering Nagercoil (UCEN)",
      designation: "Associate Professor & HOD",
      experienceType: "Academic Experience",
      fromDate: "2019-07-01",
      toDate: "Present",
      totalExperience: "5+ Years",
      responsibilities: "Head of MBA Department, curriculum delivery, business analytics lab coordinator, and Student Entrepreneurship Cell mentor."
    }
  ],
  memberships: [
    {
      institutionName: "All India Management Association (AIMA)",
      membershipCategory: "Life Member",
      membershipId: "AIMA-LM-41982",
      membershipType: "Life Member",
      yearOfJoining: "2011"
    },
    {
      institutionName: "Madras Management Association (MMA)",
      membershipCategory: "Professional Member",
      membershipId: "MMA-TN-2018",
      membershipType: "Life Member",
      yearOfJoining: "2015"
    }
  ],
  positionsHeld: [
    {
      position: "Head of the Department (HOD)",
      organisationName: "UCEN Nagercoil",
      department: "MBA",
      fromDate: "2019-07-01",
      toDate: "Present",
      responsibilities: "Academic leadership of the Master of Business Administration programme, industrial internship placements, and industry CEO guest lecture series."
    }
  ],
  achievements: [
    {
      title: "Best Management Educator Award",
      description: "Recognized by AIMA South Zone for excellence in teaching Business Analytics and Financial Modeling.",
      organisation: "All India Management Association",
      category: "Professional Recognition",
      year: "2022",
      document: "AIMA_Best_Educator.pdf"
    }
  ],
  consultancy: [
    {
      projectTitle: "Market Feasibility and Financial Viability Study for Eco-Tourism in Kanyakumari",
      client: "Tamil Nadu Tourism Development Corporation (TTDC)",
      projectDescription: "Customer demographic analysis, investment payback model, and digital marketing strategy formulation.",
      role: "Lead Management Consultant",
      duration: "6 Months (2023)",
      outcome: "Delivered comprehensive commercial viability report and generated ₹2.8 Lakhs consultancy fee."
    }
  ],
  journalPublications: [
    {
      title: "FinTech Adoption and Financial Inclusion: An Empirical Study on Coastal Micro-Enterprises in Southern Tamil Nadu",
      authors: "G. Priya and K. Ramanathan",
      journal: "Journal of Financial Services Research (Springer)",
      volume: "61",
      issue: "3",
      publicationYear: "2022",
      doi: "10.1007/s10693-022-00381-x",
      indexing: "Scopus & Web of Science (SSCI)"
    }
  ],
  books: [
    {
      title: "Financial Analytics and Corporate Decision Modeling",
      authors: "Dr. G. Priya",
      publisher: "Prentice Hall of India (PHI Learning)",
      isbn: "978-93-5443-219-0",
      publicationYear: "2023",
      pageNumbers: "360 Pages"
    }
  ],
  invitedTalks: [
    {
      title: "Strategic Financial Transformation for Tech Startups",
      event: "Tamil Nadu Startup Mission Special Conclave",
      institution: "PSG STEP, Coimbatore",
      role: "Resource Person",
      date: "2023-11-18",
      venue: "PSG Tech Auditorium"
    }
  ],
  certifications: [
    {
      name: "Financial Modeling and Valuation Analyst (FMVA)",
      organisation: "Corporate Finance Institute (CFI)",
      specialisation: "Corporate Valuation & DCF Modeling",
      completionDate: "2022-08-30",
      url: "https://corporatefinanceinstitute.com/verify",
      certificate: "CFI_FMVA_Priya.pdf"
    }
  ],
  fdpAttended: [
    {
      programmeName: "One-Week FDP on Business Analytics using Python and R",
      programmeType: "FDP",
      institution: "IIM Kozhikode",
      duration: "5 Days",
      startDate: "2022-12-05",
      endDate: "2022-12-09",
      certificate: "IIMK_Analytics_FDP.pdf"
    }
  ],
  fundedProjects: [
    {
      projectTitle: "Empirical Investigation on Digital Financial Literacy and MSME Resilience in Coastal Kanyakumari District",
      pi: "Dr. G. Priya (Associate Professor & HOD, Management Studies, UCEN)",
      coPi: "Dr. R. Sathish Kumar (Associate Professor & HOD, IT, UCEN)",
      fundingAgency: "Tamil Nadu State Council for Science and Technology (TNSCST)",
      projectCategory: "Social & Management Science Project",
      projectDescription: "Field study evaluating digital payment adoption, credit risk barriers, and mobile banking security among 500 women-led coastal micro-enterprises.",
      sanctionedAmount: "₹ 4,20,000",
      startDate: "2023-04-01",
      endDate: "2024-09-30",
      duration: "18 Months",
      status: "Completed",
      sanctionOrderNumber: "TNSCST/STP/SOC/2023/512",
      researchOutcomes: "Policy brief submitted to District Industries Centre (DIC) Nagercoil; 2 research papers published.",
      publicationsGenerated: "Journal of Financial Services Research (2022) & UGC CARE Journal Paper.",
      sanctionLetter: "TNSCST_Priya_Sanction_Order.pdf"
    }
  ]
};

// =============================================================================
// 2. STORAGE MANAGEMENT (DEMONSTRATION LOCALSTORAGE PERSISTENCE)
// =============================================================================
const STORAGE_KEY_CURRENT = 'ucen_current_faculty_profile';
const STORAGE_KEY_ROSTER = 'ucen_faculty_roster';
const STORAGE_KEY_AUTH = 'ucen_auth_session';
let memoryAuthSession = null;

function initLocalStorage() {
  try {
    if (!localStorage.getItem(STORAGE_KEY_ROSTER)) {
      const defaultRoster = [
        SAMPLE_FACULTY_DR_RAMANATHAN,
        SAMPLE_FACULTY_DR_SATHISH,
        SAMPLE_FACULTY_DR_ANITHA,
        SAMPLE_FACULTY_DR_PRIYA
      ];
      localStorage.setItem(STORAGE_KEY_ROSTER, JSON.stringify(defaultRoster));
    }

    if (!localStorage.getItem(STORAGE_KEY_CURRENT)) {
      localStorage.setItem(STORAGE_KEY_CURRENT, JSON.stringify(SAMPLE_FACULTY_DR_RAMANATHAN));
    }

    if (!localStorage.getItem(STORAGE_KEY_AUTH)) {
      const defaultSession = {
        role: 'guest',
        facultyId: null,
        name: 'Guest Visitor'
      };
      localStorage.setItem(STORAGE_KEY_AUTH, JSON.stringify(defaultSession));
    }
  } catch (e) {
    console.warn("Local storage is unavailable. Session will be kept in memory only.", e);
  }
}

function getCurrentFaculty() {
  try {
    const data = localStorage.getItem(STORAGE_KEY_CURRENT);
    if (data) return normalizeFacultyDepartment(JSON.parse(data));
  } catch (e) {
    console.error("Error reading current faculty", e);
  }
  return JSON.parse(JSON.stringify(SAMPLE_FACULTY_DR_RAMANATHAN));
}

function saveCurrentFaculty(facultyObj) {
  try {
    localStorage.setItem(STORAGE_KEY_CURRENT, JSON.stringify(facultyObj));
    // Also update roster
    const roster = getFacultyRoster();
    const idx = roster.findIndex(f => f.id === facultyObj.id);
    if (idx >= 0) {
      roster[idx] = facultyObj;
    } else {
      roster.unshift(facultyObj);
    }
    localStorage.setItem(STORAGE_KEY_ROSTER, JSON.stringify(roster));
  } catch (e) {
    console.error("Error saving faculty", e);
  }
}

function normalizeFacultyDepartment(faculty) {
  if (!faculty || !faculty.personalInfo) return faculty;
  const department = String(faculty.personalInfo.department || '').trim();
  if (/^management studies/i.test(department)) {
    faculty.personalInfo.department = 'MBA';
  }
  return faculty;
}

function getFacultyRoster() {
  try {
    const data = localStorage.getItem(STORAGE_KEY_ROSTER);
    if (data) return JSON.parse(data).map(normalizeFacultyDepartment);
  } catch (e) {
    console.error("Error reading roster", e);
  }
  return [SAMPLE_FACULTY_DR_RAMANATHAN, SAMPLE_FACULTY_DR_ANITHA];
}

function getAuthSession() {
  if (memoryAuthSession) return memoryAuthSession;
  try {
    const data = localStorage.getItem(STORAGE_KEY_AUTH);
    if (data) return JSON.parse(data);
  } catch (e) {
    console.error("Error reading session", e);
  }
  return { role: 'guest', facultyId: null, name: 'Guest Visitor' };
}

function setAuthSession(sessionObj) {
  memoryAuthSession = sessionObj;
  try {
    localStorage.setItem(STORAGE_KEY_AUTH, JSON.stringify(sessionObj));
  } catch (e) {
    console.error("Error setting session", e);
  }
  updateAuthUI();
}

// Reset data helper
function resetToDefault() {
  const defaultRoster = [
    SAMPLE_FACULTY_DR_RAMANATHAN,
    SAMPLE_FACULTY_DR_SATHISH,
    SAMPLE_FACULTY_DR_ANITHA,
    SAMPLE_FACULTY_DR_PRIYA
  ];
  localStorage.setItem(STORAGE_KEY_ROSTER, JSON.stringify(defaultRoster));
  localStorage.setItem(STORAGE_KEY_CURRENT, JSON.stringify(SAMPLE_FACULTY_DR_RAMANATHAN));
  showToast("Dataset successfully reset to official Anna University 8-department default records.", "success");
  loadFacultyIntoForm(SAMPLE_FACULTY_DR_RAMANATHAN);
  renderGeneratedProfile(SAMPLE_FACULTY_DR_RAMANATHAN);
  renderDashboard(SAMPLE_FACULTY_DR_RAMANATHAN);
}

// =============================================================================
// 3. TOAST NOTIFICATIONS
// =============================================================================
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span>${type === 'error' ? '⚠️' : '✅'}</span>
    <span>${message}</span>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// =============================================================================
// 4. VIEW ROUTING & NAVIGATION
// =============================================================================
const VIEWS = {
  landing: document.getElementById('view-landing'),
  dashboard: document.getElementById('view-dashboard'),
  form: document.getElementById('view-form'),
  profile: document.getElementById('view-profile')
};

function switchView(viewName) {
  // Hide all views
  Object.values(VIEWS).forEach(v => {
    if (v) v.classList.add('hidden');
  });

  // Remove active from nav links
  document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));

  // Show target view
  if (VIEWS[viewName]) {
    VIEWS[viewName].classList.remove('hidden');
  }

  // Set active nav button
  const navBtn = document.getElementById(`nav-${viewName}`);
  if (navBtn) navBtn.classList.add('active');

  // Trigger specific view renders
  const faculty = getCurrentFaculty();
  if (viewName === 'dashboard') {
    renderDashboard(faculty);
  } else if (viewName === 'profile') {
    renderGeneratedProfile(faculty);
  } else if (viewName === 'form') {
    loadFacultyIntoForm(faculty);
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// =============================================================================
// 5. ACCORDION CONTROLS (EXPAND & COLLAPSE)
// =============================================================================
function toggleAccordion(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.toggle('open');
}

function expandAllAccordions() {
  document.querySelectorAll('.accordion-item').forEach(item => item.classList.add('open'));
}

function collapseAllAccordions() {
  document.querySelectorAll('.accordion-item').forEach(item => item.classList.remove('open'));
}

function saveAndContinue(secNum) {
  saveDraft(false);
  const currentAcc = document.getElementById(`accordion-sec-${secNum}`);
  const nextAcc = document.getElementById(`accordion-sec-${secNum + 1}`);
  if (currentAcc) currentAcc.classList.remove('open');
  if (nextAcc) {
    nextAcc.classList.add('open');
    nextAcc.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  showToast(`Section 0${secNum} saved. Proceeding to next parameter.`);
}

// =============================================================================
// 6. DYNAMIC ADD ANOTHER / REMOVE MULTI-ENTRY BUILDERS
// =============================================================================

// Helper: Escape HTML
function esc(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// 02. Qualifications
function addQualificationRow(data = {}) {
  const container = document.getElementById('container-qualifications');
  const div = document.createElement('div');
  div.className = 'multi-entry-card qualification-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Degree Qualification Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group">
        <label class="form-label">Degree</label>
        <input type="text" class="form-control q-degree" placeholder="Ph.D., M.E., B.E., M.Tech" value="${esc(data.degree || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Specialisation</label>
        <input type="text" class="form-control q-spec" placeholder="e.g. Distributed Systems" value="${esc(data.specialisation || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Institution Name</label>
        <input type="text" class="form-control q-inst" placeholder="College / Institute Name" value="${esc(data.institution || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">University Name</label>
        <input type="text" class="form-control q-univ" placeholder="Anna University, Chennai" value="${esc(data.university || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Year of Passing</label>
        <input type="text" class="form-control q-year" placeholder="2012" value="${esc(data.yearOfPassing || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">CGPA / % & Status</label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" class="form-control q-cgpa" placeholder="8.9 / 10" value="${esc(data.cgpa || '')}" style="flex: 2;">
          <select class="form-control q-status" style="flex: 1.5;">
            <option value="Completed" ${data.status === 'Completed' ? 'selected' : ''}>Completed</option>
            <option value="Pursuing" ${data.status === 'Pursuing' ? 'selected' : ''}>Pursuing</option>
          </select>
        </div>
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 03. Experience
function addExperienceRow(data = {}) {
  const container = document.getElementById('container-experience');
  const div = document.createElement('div');
  div.className = 'multi-entry-card experience-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Academic / Professional Experience Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group">
        <label class="form-label">Institution / Organisation</label>
        <input type="text" class="form-control exp-inst" placeholder="Organisation Name" value="${esc(data.institution || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Designation</label>
        <input type="text" class="form-control exp-desig" placeholder="e.g. Associate Professor" value="${esc(data.designation || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Experience Type</label>
        <select class="form-control exp-type">
          <option value="Academic Experience" ${data.experienceType === 'Academic Experience' ? 'selected' : ''}>Academic Experience</option>
          <option value="Teaching Experience" ${data.experienceType === 'Teaching Experience' ? 'selected' : ''}>Teaching Experience</option>
          <option value="Industrial Experience" ${data.experienceType === 'Industrial Experience' ? 'selected' : ''}>Industrial Experience</option>
          <option value="Research Experience" ${data.experienceType === 'Research Experience' ? 'selected' : ''}>Research Experience</option>
          <option value="Consultancy Experience" ${data.experienceType === 'Consultancy Experience' ? 'selected' : ''}>Consultancy Experience</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control exp-from" value="${esc(data.fromDate || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">To Date</label>
        <input type="text" class="form-control exp-to" placeholder="YYYY-MM-DD or Present" value="${esc(data.toDate || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Total Duration</label>
        <input type="text" class="form-control exp-total" placeholder="e.g. 5 Years" value="${esc(data.totalExperience || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Key Responsibilities</label>
        <input type="text" class="form-control exp-resp" placeholder="Describe instruction, lab oversight, and administrative roles" value="${esc(data.responsibilities || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 04. Memberships
function addMembershipRow(data = {}) {
  const container = document.getElementById('container-memberships');
  const div = document.createElement('div');
  div.className = 'multi-entry-card membership-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Professional Institution Membership</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group">
        <label class="form-label">Professional Institution Name</label>
        <input type="text" class="form-control mem-inst" placeholder="IEEE / ISTE / ACM / CSI / IEI" value="${esc(data.institutionName || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Membership Category</label>
        <input type="text" class="form-control mem-cat" placeholder="Senior Member, Fellow, Member" value="${esc(data.membershipCategory || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Membership ID</label>
        <input type="text" class="form-control mem-id" placeholder="IEEE-SM-9382104" value="${esc(data.membershipId || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Membership Type</label>
        <select class="form-control mem-type">
          <option value="Life Member" ${data.membershipType === 'Life Member' ? 'selected' : ''}>Life Member</option>
          <option value="Annual Member" ${data.membershipType === 'Annual Member' ? 'selected' : ''}>Annual Member</option>
          <option value="Fellow" ${data.membershipType === 'Fellow' ? 'selected' : ''}>Fellow</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Year of Joining</label>
        <input type="text" class="form-control mem-year" placeholder="2014" value="${esc(data.yearOfJoining || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 05. Positions Held
function addPositionRow(data = {}) {
  const container = document.getElementById('container-positions');
  const div = document.createElement('div');
  div.className = 'multi-entry-card position-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Position / Portfolio Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group">
        <label class="form-label">Position / Designation</label>
        <input type="text" class="form-control pos-title" placeholder="e.g. HOD / NBA Coordinator" value="${esc(data.position || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Organisation Name</label>
        <input type="text" class="form-control pos-org" placeholder="University College of Engineering Nagercoil" value="${esc(data.organisationName || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Department / Unit</label>
        <input type="text" class="form-control pos-dept" placeholder="CSE / Exam Cell" value="${esc(data.department || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">From Date</label>
        <input type="text" class="form-control pos-from" placeholder="YYYY-MM-DD" value="${esc(data.fromDate || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">To Date</label>
        <input type="text" class="form-control pos-to" placeholder="YYYY-MM-DD or Present" value="${esc(data.toDate || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Responsibilities</label>
        <input type="text" class="form-control pos-resp" placeholder="Core administrative obligations and accomplishments" value="${esc(data.responsibilities || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 06. Key Achievements
function addAchievementRow(data = {}) {
  const container = document.getElementById('container-achievements');
  const div = document.createElement('div');
  div.className = 'multi-entry-card achievement-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Achievement / Patent / Honour</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Achievement Title</label>
        <input type="text" class="form-control ach-title" placeholder="Award title or Patent title" value="${esc(data.title || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Category</label>
        <input type="text" class="form-control ach-cat" placeholder="State Award / Patent Granted / Best Paper" value="${esc(data.category || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Organisation / Institution</label>
        <input type="text" class="form-control ach-org" placeholder="Issuing Body / Patent Office" value="${esc(data.organisation || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Year</label>
        <input type="text" class="form-control ach-year" placeholder="2022" value="${esc(data.year || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Supporting Document / Certificate</label>
        <input type="text" class="form-control ach-doc" placeholder="Award_Certificate.pdf" value="${esc(data.document || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Description</label>
        <textarea class="form-control ach-desc" rows="2" placeholder="Summary of the citation or patent specification">${esc(data.description || '')}</textarea>
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 07. Consultancy
function addConsultancyRow(data = {}) {
  const container = document.getElementById('container-consultancy');
  const div = document.createElement('div');
  div.className = 'multi-entry-card consultancy-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Consultancy & Testing Project</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Project / Consultancy Title</label>
        <input type="text" class="form-control con-title" placeholder="Title of testing or consultancy service" value="${esc(data.projectTitle || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Client / Organisation</label>
        <input type="text" class="form-control con-client" placeholder="Client Firm / Public Dept" value="${esc(data.client || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <input type="text" class="form-control con-role" placeholder="Principal Consultant / Testing Lead" value="${esc(data.role || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Project Duration</label>
        <input type="text" class="form-control con-duration" placeholder="e.g. 12 Months (2022 - 2023)" value="${esc(data.duration || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Project Description & Deliverables</label>
        <input type="text" class="form-control con-desc" placeholder="Scope of technical assistance and testing procedures" value="${esc(data.projectDescription || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">Outcome / Revenue Contribution (₹)</label>
        <input type="text" class="form-control con-outcome" placeholder="Institutional testing fee generated and technical benefits" value="${esc(data.outcome || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 08. Journal Publications
function addJournalRow(data = {}) {
  const container = document.getElementById('container-journals');
  const div = document.createElement('div');
  div.className = 'multi-entry-card journal-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Journal Publication Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group full-width">
        <label class="form-label">Publication Title</label>
        <input type="text" class="form-control pub-title" placeholder="Title of article" value="${esc(data.title || '')}">
      </div>
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Author Names</label>
        <input type="text" class="form-control pub-authors" placeholder="K. Ramanathan, S. Meenakshi, and V. Sundaram" value="${esc(data.authors || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Publication Year</label>
        <input type="text" class="form-control pub-year" placeholder="2023" value="${esc(data.publicationYear || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Journal Name</label>
        <input type="text" class="form-control pub-journal" placeholder="IEEE Transactions on Biomedical Engineering" value="${esc(data.journal || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Volume & Issue</label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" class="form-control pub-vol" placeholder="Vol. 70" value="${esc(data.volume || '')}" style="flex: 1;">
          <input type="text" class="form-control pub-issue" placeholder="Issue 4" value="${esc(data.issue || '')}" style="flex: 1;">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Indexing</label>
        <input type="text" class="form-control pub-index" placeholder="Scopus / Web of Science (SCI-E) / UGC CARE" value="${esc(data.indexing || '')}">
      </div>
      <div class="form-group full-width">
        <label class="form-label">DOI / Publication Link</label>
        <input type="text" class="form-control pub-doi" placeholder="10.1109/TBME.2023.3241902" value="${esc(data.doi || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 09. Books & Chapters
function addBookRow(data = {}) {
  const container = document.getElementById('container-books');
  const div = document.createElement('div');
  div.className = 'multi-entry-card book-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Book / Book Chapter Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Book / Chapter Title</label>
        <input type="text" class="form-control book-title" placeholder="Title of published book or chapter" value="${esc(data.title || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Author Names</label>
        <input type="text" class="form-control book-authors" placeholder="Author(s) as credited" value="${esc(data.authors || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Publisher</label>
        <input type="text" class="form-control book-publisher" placeholder="Springer Nature / CRC Press / Elsevier" value="${esc(data.publisher || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">ISBN Number</label>
        <input type="text" class="form-control book-isbn" placeholder="978-981-19-4821-3" value="${esc(data.isbn || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Year & Pages</label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" class="form-control book-year" placeholder="2023" value="${esc(data.publicationYear || '')}" style="flex: 1;">
          <input type="text" class="form-control book-pages" placeholder="348 Pages" value="${esc(data.pageNumbers || '')}" style="flex: 1.5;">
        </div>
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 10. Invited Speaker
function addTalkRow(data = {}) {
  const container = document.getElementById('container-talks');
  const div = document.createElement('div');
  div.className = 'multi-entry-card talk-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Invited Talk / Guest Lecture Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Talk Title</label>
        <input type="text" class="form-control talk-title" placeholder="Keynote or lecture topic" value="${esc(data.title || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <input type="text" class="form-control talk-role" placeholder="Keynote Speaker / Resource Person / Session Chair" value="${esc(data.role || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Event / Programme Name</label>
        <input type="text" class="form-control talk-event" placeholder="e.g. AICTE Sponsored Conference (ICAC)" value="${esc(data.event || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Organising Institution</label>
        <input type="text" class="form-control talk-inst" placeholder="NIT Trichy / Anna University MIT Campus" value="${esc(data.institution || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Date & Venue</label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="date" class="form-control talk-date" value="${esc(data.date || '')}" style="flex: 1.2;">
          <input type="text" class="form-control talk-venue" placeholder="Auditorium" value="${esc(data.venue || '')}" style="flex: 1;">
        </div>
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 11. Certifications
function addCertificationRow(data = {}) {
  const container = document.getElementById('container-certifications');
  const div = document.createElement('div');
  div.className = 'multi-entry-card cert-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>Professional Certification Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group">
        <label class="form-label">Certificate Name</label>
        <input type="text" class="form-control cert-name" placeholder="Deep Learning Specialization" value="${esc(data.name || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Issuing Organisation</label>
        <input type="text" class="form-control cert-org" placeholder="NPTEL - IIT Kharagpur / Coursera / AWS" value="${esc(data.organisation || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Course / Specialisation</label>
        <input type="text" class="form-control cert-spec" placeholder="Cloud Architecture / Neural Networks" value="${esc(data.specialisation || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Completion Date</label>
        <input type="date" class="form-control cert-date" value="${esc(data.completionDate || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Verification URL</label>
        <input type="text" class="form-control cert-url" placeholder="https://coursera.org/verify/..." value="${esc(data.url || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Certificate File Name</label>
        <input type="text" class="form-control cert-file" placeholder="Certificate.pdf" value="${esc(data.certificate || '')}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 12. FDP Attended
function addFdpRow(data = {}) {
  const container = document.getElementById('container-fdp');
  const div = document.createElement('div');
  div.className = 'multi-entry-card fdp-row';
  div.innerHTML = `
    <div class="multi-entry-header">
      <h4>FDP / Workshop / Seminar Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove</button>
    </div>
    <div class="form-grid-3">
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Programme Name</label>
        <input type="text" class="form-control fdp-name" placeholder="Two-Week ATAL FDP on Quantum Computing" value="${esc(data.programmeName || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Programme Type</label>
        <select class="form-control fdp-type">
          <option value="FDP" ${data.programmeType === 'FDP' ? 'selected' : ''}>FDP</option>
          <option value="Workshop" ${data.programmeType === 'Workshop' ? 'selected' : ''}>Workshop</option>
          <option value="Seminar" ${data.programmeType === 'Seminar' ? 'selected' : ''}>Seminar</option>
          <option value="Conference" ${data.programmeType === 'Conference' ? 'selected' : ''}>Conference</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Organising Institution</label>
        <input type="text" class="form-control fdp-inst" placeholder="IISc Bengaluru / IIT Madras" value="${esc(data.institution || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Duration</label>
        <input type="text" class="form-control fdp-dur" placeholder="14 Days / 1 Week" value="${esc(data.duration || '')}">
      </div>
      <div class="form-group">
        <label class="form-label">Timeline (Start to End)</label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="date" class="form-control fdp-start" value="${esc(data.startDate || '')}" style="flex: 1;">
          <input type="date" class="form-control fdp-end" value="${esc(data.endDate || '')}" style="flex: 1;">
        </div>
      </div>
    </div>
  `;
  container.appendChild(div);
}

// 13. FUNDED PROJECTS & RESEARCH GRANTS (DEDICATED VISUALLY HIGHLIGHTED PARAMETER)
function addFundedProjectRow(data = {}) {
  const container = document.getElementById('container-funded-projects');
  const div = document.createElement('div');
  div.className = 'multi-entry-card funded-project-row';
  div.style.border = '1.5px solid var(--primary)';
  div.style.background = '#ffffff';

  div.innerHTML = `
    <div class="multi-entry-header" style="border-bottom-color: var(--primary);">
      <h4 style="color: var(--primary); font-size: 0.9rem;">⭐ Extramural Funded Research Project Entry</h4>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.multi-entry-card').remove(); calculateCompletionPercentage();">✕ Remove Project</button>
    </div>
    <div class="form-grid-2">
      <div class="form-group full-width">
        <label class="form-label">Project Title <span class="required">*</span></label>
        <input type="text" class="form-control fp-title" placeholder="Title of the research grant project" value="${esc(data.projectTitle || '')}" required>
      </div>

      <div class="form-group">
        <label class="form-label">Principal Investigator (PI) <span class="required">*</span></label>
        <input type="text" class="form-control fp-pi" placeholder="Dr. K. Ramanathan (PI)" value="${esc(data.pi || '')}" required>
      </div>

      <div class="form-group">
        <label class="form-label">Co-Principal Investigator (Co-PI)</label>
        <input type="text" class="form-control fp-copi" placeholder="Co-PI Name(s) and Affiliations" value="${esc(data.coPi || '')}">
      </div>

      <div class="form-group">
        <label class="form-label">Funding Agency <span class="required">*</span></label>
        <input type="text" class="form-control fp-agency" placeholder="DST, SERB, AICTE, DRDO, CSIR, TNSCST" value="${esc(data.fundingAgency || '')}" required>
      </div>

      <div class="form-group">
        <label class="form-label">Sanctioned Amount (₹) <span class="required">*</span></label>
        <input type="text" class="form-control fp-amount" placeholder="₹ 48,50,000" value="${esc(data.sanctionedAmount || '')}" style="font-weight: 700; color: var(--primary);" required>
      </div>

      <div class="form-group">
        <label class="form-label">Grant / Sanction Order Number</label>
        <input type="text" class="form-control fp-order" placeholder="DST/TDT/BDD/2022/194-G" value="${esc(data.sanctionOrderNumber || '')}">
      </div>

      <div class="form-group">
        <label class="form-label">Project Status</label>
        <select class="form-control fp-status">
          <option value="Proposed" ${data.status === 'Proposed' ? 'selected' : ''}>Proposed</option>
          <option value="Approved" ${data.status === 'Approved' ? 'selected' : ''}>Approved</option>
          <option value="Ongoing" ${data.status === 'Ongoing' ? 'selected' : ''}>Ongoing</option>
          <option value="Completed" ${data.status === 'Completed' ? 'selected' : ''}>Completed</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Project Category</label>
        <input type="text" class="form-control fp-category" placeholder="Applied R&D / MODROBS / RPS" value="${esc(data.projectCategory || '')}">
      </div>

      <div class="form-group">
        <label class="form-label">Project Duration</label>
        <input type="text" class="form-control fp-duration" placeholder="36 Months (3 Years)" value="${esc(data.duration || '')}">
      </div>

      <div class="form-group">
        <label class="form-label">Start Date</label>
        <input type="date" class="form-control fp-start" value="${esc(data.startDate || '')}">
      </div>

      <div class="form-group">
        <label class="form-label">End Date</label>
        <input type="date" class="form-control fp-end" value="${esc(data.endDate || '')}">
      </div>

      <div class="form-group full-width">
        <label class="form-label">Project Description</label>
        <textarea class="form-control fp-desc" rows="2" placeholder="Summary of the experimental testbed, objectives and clinical/industrial translational scope">${esc(data.projectDescription || '')}</textarea>
      </div>

      <div class="form-group full-width">
        <label class="form-label">Research Outcomes & Deliverables</label>
        <textarea class="form-control fp-outcomes" rows="2" placeholder="Patents filed, prototype testing units deployed, lab established, scholars funded">${esc(data.researchOutcomes || '')}</textarea>
      </div>

      <div class="form-group full-width">
        <label class="form-label">Publications Generated from Project</label>
        <input type="text" class="form-control fp-pubs" placeholder="List journal papers or conference proceedings emerging from this grant" value="${esc(data.publicationsGenerated || '')}">
      </div>

      <div class="form-group full-width">
        <label class="form-label">Supporting Sanction Letter / Order Upload</label>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
          <input type="text" class="form-control fp-letter" placeholder="DST_Sanction_Order.pdf" value="${esc(data.sanctionLetter || '')}" style="flex: 1;">
          <button type="button" class="btn btn-sm btn-outline" onclick="simulateFileUpload(this)" style="flex-shrink: 0;">📎 Attach PDF</button>
        </div>
      </div>
    </div>
  `;
  container.appendChild(div);
}

function simulateFileUpload(btn) {
  const input = btn.previousElementSibling;
  const fileName = `Sanction_Order_${Math.floor(1000 + Math.random() * 9000)}.pdf`;
  if (input) input.value = fileName;
  showToast(`Simulated attachment uploaded: ${fileName}`);
}

// =============================================================================
// 7. FORM SERIALIZATION & EXTRACTION
// =============================================================================
function extractFacultyFromForm() {
  const existing = getCurrentFaculty();

  const areasVal = document.getElementById('f-specialisations').value;
  const areas = areasVal
    ? areasVal.split(',').map(s => s.trim()).filter(Boolean)
    : [];

  const faculty = {
    id: existing.id || `ucen-fac-${Date.now()}`,
    status: existing.status || 'Draft',
    personalInfo: {
      fullName: document.getElementById('f-fullName').value.trim(),
      designation: document.getElementById('f-designation').value,
      department: document.getElementById('f-department').value,
      employeeId: document.getElementById('f-employeeId').value.trim(),
      email: document.getElementById('f-email').value.trim(),
      contactNumber: document.getElementById('f-contact').value.trim(),
      doj: document.getElementById('f-doj').value,
      dob: document.getElementById('f-dob').value,
      photo: document.getElementById('form-photo-preview').src,
      areasOfSpecialisation: areas,
      biography: document.getElementById('f-biography').value.trim()
    },
    qualifications: [],
    experience: [],
    memberships: [],
    positionsHeld: [],
    achievements: [],
    consultancy: [],
    journalPublications: [],
    books: [],
    invitedTalks: [],
    certifications: [],
    fdpAttended: [],
    fundedProjects: []
  };

  // Qualifications
  document.querySelectorAll('.qualification-row').forEach(row => {
    faculty.qualifications.push({
      degree: row.querySelector('.q-degree').value.trim(),
      specialisation: row.querySelector('.q-spec').value.trim(),
      institution: row.querySelector('.q-inst').value.trim(),
      university: row.querySelector('.q-univ').value.trim(),
      yearOfPassing: row.querySelector('.q-year').value.trim(),
      cgpa: row.querySelector('.q-cgpa').value.trim(),
      status: row.querySelector('.q-status').value
    });
  });

  // Experience
  document.querySelectorAll('.experience-row').forEach(row => {
    faculty.experience.push({
      institution: row.querySelector('.exp-inst').value.trim(),
      designation: row.querySelector('.exp-desig').value.trim(),
      experienceType: row.querySelector('.exp-type').value,
      fromDate: row.querySelector('.exp-from').value,
      toDate: row.querySelector('.exp-to').value.trim(),
      totalExperience: row.querySelector('.exp-total').value.trim(),
      responsibilities: row.querySelector('.exp-resp').value.trim()
    });
  });

  // Memberships
  document.querySelectorAll('.membership-row').forEach(row => {
    faculty.memberships.push({
      institutionName: row.querySelector('.mem-inst').value.trim(),
      membershipCategory: row.querySelector('.mem-cat').value.trim(),
      membershipId: row.querySelector('.mem-id').value.trim(),
      membershipType: row.querySelector('.mem-type').value,
      yearOfJoining: row.querySelector('.mem-year').value.trim()
    });
  });

  // Positions
  document.querySelectorAll('.position-row').forEach(row => {
    faculty.positionsHeld.push({
      position: row.querySelector('.pos-title').value.trim(),
      organisationName: row.querySelector('.pos-org').value.trim(),
      department: row.querySelector('.pos-dept').value.trim(),
      fromDate: row.querySelector('.pos-from').value.trim(),
      toDate: row.querySelector('.pos-to').value.trim(),
      responsibilities: row.querySelector('.pos-resp').value.trim()
    });
  });

  // Achievements
  document.querySelectorAll('.achievement-row').forEach(row => {
    faculty.achievements.push({
      title: row.querySelector('.ach-title').value.trim(),
      category: row.querySelector('.ach-cat').value.trim(),
      organisation: row.querySelector('.ach-org').value.trim(),
      year: row.querySelector('.ach-year').value.trim(),
      document: row.querySelector('.ach-doc').value.trim(),
      description: row.querySelector('.ach-desc').value.trim()
    });
  });

  // Consultancy
  document.querySelectorAll('.consultancy-row').forEach(row => {
    faculty.consultancy.push({
      projectTitle: row.querySelector('.con-title').value.trim(),
      client: row.querySelector('.con-client').value.trim(),
      role: row.querySelector('.con-role').value.trim(),
      duration: row.querySelector('.con-duration').value.trim(),
      projectDescription: row.querySelector('.con-desc').value.trim(),
      outcome: row.querySelector('.con-outcome').value.trim()
    });
  });

  // Journals
  document.querySelectorAll('.journal-row').forEach(row => {
    faculty.journalPublications.push({
      title: row.querySelector('.pub-title').value.trim(),
      authors: row.querySelector('.pub-authors').value.trim(),
      publicationYear: row.querySelector('.pub-year').value.trim(),
      journal: row.querySelector('.pub-journal').value.trim(),
      volume: row.querySelector('.pub-vol').value.trim(),
      issue: row.querySelector('.pub-issue').value.trim(),
      indexing: row.querySelector('.pub-index').value.trim(),
      doi: row.querySelector('.pub-doi').value.trim()
    });
  });

  // Books
  document.querySelectorAll('.book-row').forEach(row => {
    faculty.books.push({
      title: row.querySelector('.book-title').value.trim(),
      authors: row.querySelector('.book-authors').value.trim(),
      publisher: row.querySelector('.book-publisher').value.trim(),
      isbn: row.querySelector('.book-isbn').value.trim(),
      publicationYear: row.querySelector('.book-year').value.trim(),
      pageNumbers: row.querySelector('.book-pages').value.trim()
    });
  });

  // Talks
  document.querySelectorAll('.talk-row').forEach(row => {
    faculty.invitedTalks.push({
      title: row.querySelector('.talk-title').value.trim(),
      role: row.querySelector('.talk-role').value.trim(),
      event: row.querySelector('.talk-event').value.trim(),
      institution: row.querySelector('.talk-inst').value.trim(),
      date: row.querySelector('.talk-date').value,
      venue: row.querySelector('.talk-venue').value.trim()
    });
  });

  // Certifications
  document.querySelectorAll('.cert-row').forEach(row => {
    faculty.certifications.push({
      name: row.querySelector('.cert-name').value.trim(),
      organisation: row.querySelector('.cert-org').value.trim(),
      specialisation: row.querySelector('.cert-spec').value.trim(),
      completionDate: row.querySelector('.cert-date').value,
      url: row.querySelector('.cert-url').value.trim(),
      certificate: row.querySelector('.cert-file').value.trim()
    });
  });

  // FDP
  document.querySelectorAll('.fdp-row').forEach(row => {
    faculty.fdpAttended.push({
      programmeName: row.querySelector('.fdp-name').value.trim(),
      programmeType: row.querySelector('.fdp-type').value,
      institution: row.querySelector('.fdp-inst').value.trim(),
      duration: row.querySelector('.fdp-dur').value.trim(),
      startDate: row.querySelector('.fdp-start').value,
      endDate: row.querySelector('.fdp-end').value
    });
  });

  // 13. FUNDED PROJECTS & RESEARCH GRANTS
  document.querySelectorAll('.funded-project-row').forEach(row => {
    faculty.fundedProjects.push({
      projectTitle: row.querySelector('.fp-title').value.trim(),
      pi: row.querySelector('.fp-pi').value.trim(),
      coPi: row.querySelector('.fp-copi').value.trim(),
      fundingAgency: row.querySelector('.fp-agency').value.trim(),
      sanctionedAmount: row.querySelector('.fp-amount').value.trim(),
      sanctionOrderNumber: row.querySelector('.fp-order').value.trim(),
      status: row.querySelector('.fp-status').value,
      projectCategory: row.querySelector('.fp-category').value.trim(),
      duration: row.querySelector('.fp-duration').value.trim(),
      startDate: row.querySelector('.fp-start').value,
      endDate: row.querySelector('.fp-end').value,
      projectDescription: row.querySelector('.fp-desc').value.trim(),
      researchOutcomes: row.querySelector('.fp-outcomes').value.trim(),
      publicationsGenerated: row.querySelector('.fp-pubs').value.trim(),
      sanctionLetter: row.querySelector('.fp-letter').value.trim()
    });
  });

  return faculty;
}

function loadFacultyIntoForm(faculty) {
  if (!faculty) return;
  const p = faculty.personalInfo || {};

  document.getElementById('f-fullName').value = p.fullName || '';
  document.getElementById('f-designation').value = p.designation || 'Professor & Head of Department';
  document.getElementById('f-department').value = p.department || 'Computer Science and Engineering';
  document.getElementById('f-employeeId').value = p.employeeId || '';
  document.getElementById('f-email').value = p.email || '';
  document.getElementById('f-contact').value = p.contactNumber || '';
  document.getElementById('f-doj').value = p.doj || '';
  document.getElementById('f-dob').value = p.dob || '';
  document.getElementById('f-specialisations').value = (p.areasOfSpecialisation || []).join(', ');
  document.getElementById('f-biography').value = p.biography || '';

  const preview = document.getElementById('form-photo-preview');
  if (preview) preview.src = p.photo || 'assets/default-profile.png';

  // Clear all multi-containers
  [
    'container-qualifications',
    'container-experience',
    'container-memberships',
    'container-positions',
    'container-achievements',
    'container-consultancy',
    'container-journals',
    'container-books',
    'container-talks',
    'container-certifications',
    'container-fdp',
    'container-funded-projects'
  ].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = '';
  });

  // Populate rows
  (faculty.qualifications || []).forEach(addQualificationRow);
  (faculty.experience || []).forEach(addExperienceRow);
  (faculty.memberships || []).forEach(addMembershipRow);
  (faculty.positionsHeld || []).forEach(addPositionRow);
  (faculty.achievements || []).forEach(addAchievementRow);
  (faculty.consultancy || []).forEach(addConsultancyRow);
  (faculty.journalPublications || []).forEach(addJournalRow);
  (faculty.books || []).forEach(addBookRow);
  (faculty.invitedTalks || []).forEach(addTalkRow);
  (faculty.certifications || []).forEach(addCertificationRow);
  (faculty.fdpAttended || []).forEach(addFdpRow);
  (faculty.fundedProjects || []).forEach(addFundedProjectRow);

  // If empty, add default placeholder row
  if ((faculty.qualifications || []).length === 0) addQualificationRow();
  if ((faculty.experience || []).length === 0) addExperienceRow();
  if ((faculty.fundedProjects || []).length === 0) addFundedProjectRow();

  calculateCompletionPercentage();
}

function calculateCompletionPercentage() {
  const f = extractFacultyFromForm();
  let filledCount = 0;
  let totalMetrics = 13; // 13 parameters

  if (f.personalInfo?.fullName && f.personalInfo?.email) filledCount++;
  if ((f.qualifications || []).length > 0) filledCount++;
  if ((f.experience || []).length > 0) filledCount++;
  if ((f.memberships || []).length > 0) filledCount++;
  if ((f.positionsHeld || []).length > 0) filledCount++;
  if ((f.achievements || []).length > 0) filledCount++;
  if ((f.consultancy || []).length > 0) filledCount++;
  if ((f.journalPublications || []).length > 0) filledCount++;
  if ((f.books || []).length > 0) filledCount++;
  if ((f.invitedTalks || []).length > 0) filledCount++;
  if ((f.certifications || []).length > 0) filledCount++;
  if ((f.fdpAttended || []).length > 0) filledCount++;
  if ((f.fundedProjects || []).length > 0) filledCount++;

  const pct = Math.round((filledCount / totalMetrics) * 100);

  const formPct = document.getElementById('form-completion-pct');
  const formBar = document.getElementById('form-progress-bar');
  if (formPct) formPct.textContent = `${pct}%`;
  if (formBar) formBar.style.width = `${pct}%`;

  const dashNum = document.getElementById('dash-completion-number');
  const dashBar = document.getElementById('dash-progress-fill');
  if (dashNum) dashNum.textContent = `${pct}%`;
  if (dashBar) dashBar.style.width = `${pct}%`;

  return pct;
}

function saveDraft(notify = true) {
  const faculty = extractFacultyFromForm();
  faculty.status = 'Draft';
  saveCurrentFaculty(faculty);
  calculateCompletionPercentage();
  const hint = document.getElementById('form-last-saved-hint');
  if (hint) {
    hint.textContent = `Draft saved at ${new Date().toLocaleTimeString()} (local storage)`;
  }
  if (notify) {
    showToast("Profile draft saved successfully.");
  }
}

// =============================================================================
// 8. GENERATED FACULTY PROFILE (ACADEMIC CV) RENDERER
// =============================================================================
function renderGeneratedProfile(faculty) {
  if (!faculty) return;
  const p = faculty.personalInfo || {};

  document.getElementById('cv-fullName').textContent = p.fullName || 'Faculty Member';
  document.getElementById('cv-designation').textContent = p.designation || '';
  document.getElementById('cv-department').textContent = p.department || '';
  document.getElementById('cv-empId').textContent = p.employeeId || 'N/A';
  document.getElementById('cv-email').textContent = p.email || 'N/A';
  document.getElementById('cv-contact').textContent = p.contactNumber || 'N/A';
  document.getElementById('cv-doj').textContent = p.doj || 'N/A';

  const photoEl = document.getElementById('cv-photo');
  if (photoEl) photoEl.src = p.photo || 'assets/default-profile.png';

  // Bio
  const bioSec = document.getElementById('cv-sec-bio');
  const bioText = document.getElementById('cv-biography');
  if (p.biography) {
    bioSec.classList.remove('hidden');
    bioText.textContent = p.biography;
  } else {
    bioSec.classList.add('hidden');
  }

  // Specialisations
  const specSec = document.getElementById('cv-sec-specialisations');
  const specTags = document.getElementById('cv-specialisations-tags');
  specTags.innerHTML = '';
  if (p.areasOfSpecialisation && p.areasOfSpecialisation.length > 0) {
    specSec.classList.remove('hidden');
    p.areasOfSpecialisation.forEach(area => {
      const span = document.createElement('span');
      span.className = 'tag-chip';
      span.textContent = area;
      specTags.appendChild(span);
    });
  } else {
    specSec.classList.add('hidden');
  }

  // 13. FUNDED PROJECTS & RESEARCH GRANTS (HIGHLIGHTED IN CV)
  const fundedList = document.getElementById('cv-funded-list');
  fundedList.innerHTML = '';
  const fundedProjects = faculty.fundedProjects || [];
  if (fundedProjects.length === 0) {
    fundedList.innerHTML = `<p class="form-hint">No external funded research grants recorded currently.</p>`;
  } else {
    fundedProjects.forEach(proj => {
      const card = document.createElement('div');
      card.className = 'cv-funded-project-card';
      card.innerHTML = `
        <div class="cv-funded-header">
          <div>
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem;">
              <span class="sec-badge" style="background: var(--primary); color: #ffffff;">${esc(proj.fundingAgency || 'Agency')}</span>
              <span class="tag-chip" style="background: #e0e7ff; color: #3730a3;">● ${esc(proj.status || 'Ongoing')}</span>
              ${proj.projectCategory ? `<span style="font-size: 0.72rem; color: var(--text-muted);">${esc(proj.projectCategory)}</span>` : ''}
            </div>
            <div class="cv-item-title" style="font-size: 1rem;">${esc(proj.projectTitle)}</div>
          </div>
          <div class="cv-grant-amount">${esc(proj.sanctionedAmount || 'Grant')}</div>
        </div>

        <div style="font-size: 0.8rem; background: #ffffff; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light); margin: 0.5rem 0;">
          <div><strong>Principal Investigator:</strong> ${esc(proj.pi)}</div>
          ${proj.coPi ? `<div><strong>Co-Principal Investigator:</strong> ${esc(proj.coPi)}</div>` : ''}
          <div><strong>Grant / Sanction Order No:</strong> <span style="font-family: monospace; color: var(--primary); font-weight: 700;">${esc(proj.sanctionOrderNumber || 'N/A')}</span></div>
          <div><strong>Duration:</strong> ${esc(proj.duration || 'N/A')} (${esc(proj.startDate || '')} to ${esc(proj.endDate || '')})</div>
        </div>

        ${proj.projectDescription ? `<div class="cv-item-desc"><strong>Project Description:</strong> ${esc(proj.projectDescription)}</div>` : ''}
        ${proj.researchOutcomes ? `<div class="cv-item-desc" style="margin-top: 0.35rem;"><strong>Research Outcomes:</strong> ${esc(proj.researchOutcomes)}</div>` : ''}
        ${proj.publicationsGenerated ? `<div class="cv-item-desc" style="margin-top: 0.35rem;"><strong>Publications from Grant:</strong> ${esc(proj.publicationsGenerated)}</div>` : ''}
        ${proj.sanctionLetter ? `<div style="font-size: 0.75rem; color: var(--primary); margin-top: 0.5rem;">📄 Attached Order Document: <strong>${esc(proj.sanctionLetter)}</strong></div>` : ''}
      `;
      fundedList.appendChild(card);
    });
  }

  // 02. Qualifications Table
  const qualsBody = document.getElementById('cv-qualifications-body');
  qualsBody.innerHTML = '';
  (faculty.qualifications || []).forEach(q => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><strong>${esc(q.degree)}</strong></td>
      <td>${esc(q.specialisation)}</td>
      <td>${esc(q.institution)}</td>
      <td>${esc(q.university)}</td>
      <td>${esc(q.yearOfPassing)}</td>
      <td>${esc(q.cgpa)}</td>
      <td><span class="tag-chip" style="background: var(--accent-green-light); color: #065f46;">${esc(q.status)}</span></td>
    `;
    qualsBody.appendChild(tr);
  });

  // 03. Experience List
  const expList = document.getElementById('cv-experience-list');
  expList.innerHTML = '';
  (faculty.experience || []).forEach(exp => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
        <div>
          <div class="cv-item-title">${esc(exp.designation)}</div>
          <div class="cv-item-sub">${esc(exp.institution)}</div>
        </div>
        <span class="tag-chip" style="background: var(--primary-light); color: var(--primary);">${esc(exp.experienceType)}</span>
      </div>
      <div class="cv-item-meta">${esc(exp.fromDate)} to ${esc(exp.toDate)} • Total Duration: <strong>${esc(exp.totalExperience)}</strong></div>
      ${exp.responsibilities ? `<div class="cv-item-desc">${esc(exp.responsibilities)}</div>` : ''}
    `;
    expList.appendChild(div);
  });

  // 04. Memberships
  const memList = document.getElementById('cv-memberships-list');
  memList.innerHTML = '';
  (faculty.memberships || []).forEach(mem => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div class="cv-item-title">${esc(mem.institutionName)}</div>
      <div class="cv-item-sub">${esc(mem.membershipCategory)} • ${esc(mem.membershipType)}</div>
      <div class="cv-item-meta">ID: <strong>${esc(mem.membershipId)}</strong> • Joined: ${esc(mem.yearOfJoining)}</div>
    `;
    memList.appendChild(div);
  });

  // 05. Positions Held
  const posList = document.getElementById('cv-positions-list');
  posList.innerHTML = '';
  (faculty.positionsHeld || []).forEach(pos => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between;">
        <div class="cv-item-title">${esc(pos.position)}</div>
        <span class="cv-item-meta">${esc(pos.fromDate)} to ${esc(pos.toDate)}</span>
      </div>
      <div class="cv-item-sub">${esc(pos.organisationName)} • ${esc(pos.department)}</div>
      ${pos.responsibilities ? `<div class="cv-item-desc">${esc(pos.responsibilities)}</div>` : ''}
    `;
    posList.appendChild(div);
  });

  // 06. Achievements
  const achList = document.getElementById('cv-achievements-list');
  achList.innerHTML = '';
  (faculty.achievements || []).forEach(ach => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between;">
        <div class="cv-item-title">${esc(ach.title)}</div>
        <span class="tag-chip">${esc(ach.category)} (${esc(ach.year)})</span>
      </div>
      <div class="cv-item-sub">${esc(ach.organisation)}</div>
      ${ach.description ? `<div class="cv-item-desc">${esc(ach.description)}</div>` : ''}
    `;
    achList.appendChild(div);
  });

  // 07. Consultancy
  const conList = document.getElementById('cv-consultancy-list');
  conList.innerHTML = '';
  (faculty.consultancy || []).forEach(con => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div class="cv-item-title">${esc(con.projectTitle)}</div>
      <div class="cv-item-sub">Client: ${esc(con.client)} • Role: ${esc(con.role)} (${esc(con.duration)})</div>
      ${con.projectDescription ? `<div class="cv-item-desc">${esc(con.projectDescription)}</div>` : ''}
      ${con.outcome ? `<div class="cv-item-desc" style="color: var(--primary);"><strong>Outcome:</strong> ${esc(con.outcome)}</div>` : ''}
    `;
    conList.appendChild(div);
  });

  // 08. Journals
  const jourList = document.getElementById('cv-journals-list');
  jourList.innerHTML = '';
  (faculty.journalPublications || []).forEach(pub => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
        <div class="cv-item-title">${esc(pub.title)}</div>
        <span class="tag-chip" style="background: var(--accent-green-light); color: #065f46;">${esc(pub.indexing || 'Indexed')}</span>
      </div>
      <div class="cv-item-desc"><strong>Authors:</strong> ${esc(pub.authors)}</div>
      <div class="cv-item-meta"><em>${esc(pub.journal)}</em>, Vol. ${esc(pub.volume)}, Issue ${esc(pub.issue)}, ${esc(pub.publicationYear)}</div>
      ${pub.doi ? `<div style="font-size: 0.75rem; font-family: monospace; color: var(--primary); margin-top: 0.2rem;">DOI: ${esc(pub.doi)}</div>` : ''}
    `;
    jourList.appendChild(div);
  });

  // 09. Books
  const bookList = document.getElementById('cv-books-list');
  bookList.innerHTML = '';
  (faculty.books || []).forEach(b => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div class="cv-item-title">${esc(b.title)}</div>
      <div class="cv-item-desc"><strong>Authors:</strong> ${esc(b.authors)}</div>
      <div class="cv-item-meta">Publisher: <strong>${esc(b.publisher)}</strong> • ISBN: ${esc(b.isbn)} • Year: ${esc(b.publicationYear)} • ${esc(b.pageNumbers)}</div>
    `;
    bookList.appendChild(div);
  });

  // 10. Talks
  const talkList = document.getElementById('cv-talks-list');
  talkList.innerHTML = '';
  (faculty.invitedTalks || []).forEach(t => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between;">
        <div class="cv-item-title">${esc(t.title)}</div>
        <span class="tag-chip">${esc(t.role)}</span>
      </div>
      <div class="cv-item-sub">${esc(t.event)}</div>
      <div class="cv-item-meta">Organiser: ${esc(t.institution)} • Date: ${esc(t.date)} • Venue: ${esc(t.venue)}</div>
    `;
    talkList.appendChild(div);
  });

  // 11. Certifications
  const certList = document.getElementById('cv-certifications-list');
  certList.innerHTML = '';
  (faculty.certifications || []).forEach(c => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div class="cv-item-title">${esc(c.name)}</div>
      <div class="cv-item-sub">${esc(c.organisation)}</div>
      <div class="cv-item-meta">${esc(c.specialisation)} • Completed: ${esc(c.completionDate)}</div>
    `;
    certList.appendChild(div);
  });

  // 12. FDP
  const fdpList = document.getElementById('cv-fdp-list');
  fdpList.innerHTML = '';
  (faculty.fdpAttended || []).forEach(f => {
    const div = document.createElement('div');
    div.className = 'cv-item-card';
    div.innerHTML = `
      <div style="display: flex; justify-content: space-between;">
        <div class="cv-item-title">${esc(f.programmeName)}</div>
        <span class="tag-chip">${esc(f.programmeType)}</span>
      </div>
      <div class="cv-item-sub">${esc(f.institution)}</div>
      <div class="cv-item-meta">Duration: ${esc(f.duration)} (${esc(f.startDate)} to ${esc(f.endDate)})</div>
    `;
    fdpList.appendChild(div);
  });
}

// =============================================================================
// 9. FACULTY DASHBOARD RENDERER
// =============================================================================
function renderDashboard(faculty) {
  if (!faculty) return;
  const p = faculty.personalInfo || {};

  document.getElementById('dash-greeting').textContent = `Welcome, ${p.fullName || 'Faculty Member'}`;
  document.getElementById('dash-designation').textContent = p.designation || 'Faculty Member';
  document.getElementById('dash-department').textContent = p.department || 'University College of Engineering Nagercoil';

  const photoEl = document.getElementById('dash-photo');
  if (photoEl) photoEl.src = p.photo || 'assets/default-profile.png';

  const statusBadge = document.getElementById('dash-status-badge');
  const statusText = document.getElementById('dash-status-text');
  if (faculty.status === 'Published') {
    statusBadge.className = 'profile-status-badge status-published';
    statusText.textContent = 'Profile Published & Verified';
  } else {
    statusBadge.className = 'profile-status-badge status-draft';
    statusText.textContent = 'Draft in Progress';
  }

  // Stats
  const grantCount = (faculty.fundedProjects || []).length;
  document.getElementById('dash-stat-grants').textContent = `${grantCount} Project${grantCount === 1 ? '' : 's'}`;
  document.getElementById('dash-stat-pubs').textContent = `${(faculty.journalPublications || []).length} Papers`;
  document.getElementById('dash-stat-quals').textContent = `${(faculty.qualifications || []).length} Degrees`;
  document.getElementById('dash-stat-exp').textContent = (faculty.experience || []).length > 0 ? (faculty.experience[0].totalExperience || '10+ Yrs') : '8+ Yrs';

  calculateCompletionPercentage();
}

// =============================================================================
// 10. AUTHENTICATION & SESSION UI
// =============================================================================
function updateAuthUI() {
  const session = getAuthSession();
  const authActionsGroup = document.getElementById('auth-actions-group');
  const userProfileBadge = document.getElementById('user-profile-badge');
  const headerUserName = document.getElementById('header-user-name');
  const headerUserRole = document.getElementById('header-user-role');
  const headerUserAvatar = document.getElementById('header-user-avatar');

  if (session.role === 'guest') {
    if (authActionsGroup) authActionsGroup.classList.remove('hidden');
    if (userProfileBadge) userProfileBadge.classList.add('hidden');
  } else {
    if (authActionsGroup) authActionsGroup.classList.add('hidden');
    if (userProfileBadge) {
      userProfileBadge.classList.remove('hidden');
      if (headerUserName) headerUserName.textContent = session.name;
      if (headerUserRole) headerUserRole.textContent = 'Faculty';
      if (headerUserAvatar && session.photo) headerUserAvatar.src = session.photo;
    }
  }
}

// =============================================================================
// 12. INITIALIZATION & EVENT LISTENERS
// =============================================================================
document.addEventListener('DOMContentLoaded', () => {
  initLocalStorage();
  const currentFaculty = getCurrentFaculty();
  updateAuthUI();

  // Navigation handlers
  document.getElementById('nav-home')?.addEventListener('click', () => switchView('landing'));
  document.getElementById('nav-brand-home')?.addEventListener('click', (e) => {
    e.preventDefault();
    switchView('dashboard');
  });
  document.getElementById('nav-dashboard')?.addEventListener('click', () => switchView('dashboard'));
  document.getElementById('nav-form')?.addEventListener('click', () => switchView('form'));
  document.getElementById('nav-profile')?.addEventListener('click', () => switchView('profile'));

  // Hero Actions
  document.getElementById('hero-btn-login')?.addEventListener('click', () => {
    document.getElementById('modal-auth').classList.remove('hidden');
  });
  document.getElementById('hero-btn-create')?.addEventListener('click', () => {
    switchView('form');
  });

  // Demo Evaluator Buttons
  document.getElementById('demo-faculty-1')?.addEventListener('click', () => {
    saveCurrentFaculty(SAMPLE_FACULTY_DR_RAMANATHAN);
    setAuthSession({
      role: 'faculty',
      facultyId: SAMPLE_FACULTY_DR_RAMANATHAN.id,
      name: SAMPLE_FACULTY_DR_RAMANATHAN.personalInfo.fullName,
      photo: SAMPLE_FACULTY_DR_RAMANATHAN.personalInfo.photo
    });
    showToast("Logged in as Dr. K. Ramanathan (Professor & HOD, CSE)");
    switchView('dashboard');
  });

  document.getElementById('demo-faculty-it')?.addEventListener('click', () => {
    saveCurrentFaculty(SAMPLE_FACULTY_DR_SATHISH);
    setAuthSession({
      role: 'faculty',
      facultyId: SAMPLE_FACULTY_DR_SATHISH.id,
      name: SAMPLE_FACULTY_DR_SATHISH.personalInfo.fullName,
      photo: SAMPLE_FACULTY_DR_SATHISH.personalInfo.photo
    });
    showToast("Logged in as Dr. R. Sathish Kumar (Associate Professor & HOD, IT)");
    switchView('dashboard');
  });

  document.getElementById('demo-faculty-2')?.addEventListener('click', () => {
    saveCurrentFaculty(SAMPLE_FACULTY_DR_ANITHA);
    setAuthSession({
      role: 'faculty',
      facultyId: SAMPLE_FACULTY_DR_ANITHA.id,
      name: SAMPLE_FACULTY_DR_ANITHA.personalInfo.fullName,
      photo: SAMPLE_FACULTY_DR_ANITHA.personalInfo.photo
    });
    showToast("Logged in as Dr. M. Anitha (Associate Professor, ECE)");
    switchView('dashboard');
  });

  document.getElementById('demo-faculty-mba')?.addEventListener('click', () => {
    saveCurrentFaculty(SAMPLE_FACULTY_DR_PRIYA);
    setAuthSession({
      role: 'faculty',
      facultyId: SAMPLE_FACULTY_DR_PRIYA.id,
      name: SAMPLE_FACULTY_DR_PRIYA.personalInfo.fullName,
      photo: SAMPLE_FACULTY_DR_PRIYA.personalInfo.photo
    });
    showToast("Logged in as Dr. G. Priya (Associate Professor & HOD, MBA)");
    switchView('dashboard');
  });

  // Reset Data Handler
  document.getElementById('btn-reset-data')?.addEventListener('click', () => {
    if (confirm("Reset portal to official UCEN default faculty records?")) {
      resetToDefault();
    }
  });

  // Dashboard Buttons
  document.getElementById('dash-btn-edit')?.addEventListener('click', () => switchView('form'));
  document.getElementById('dash-btn-view')?.addEventListener('click', () => switchView('profile'));
  document.getElementById('dash-btn-print')?.addEventListener('click', () => {
    switchView('profile');
    setTimeout(() => window.print(), 300);
  });
  document.getElementById('dash-btn-create-fresh')?.addEventListener('click', () => {
    if (confirm("Clear form to enter a brand new faculty profile?")) {
      const blankFaculty = {
        id: `ucen-fac-${Date.now()}`,
        status: 'Draft',
        personalInfo: {
          fullName: '',
          designation: 'Assistant Professor',
          department: 'Computer Science and Engineering',
          employeeId: '',
          email: '',
          contactNumber: '',
          doj: new Date().toISOString().split('T')[0],
          areasOfSpecialisation: [],
          biography: ''
        },
        qualifications: [],
        experience: [],
        memberships: [],
        positionsHeld: [],
        achievements: [],
        consultancy: [],
        journalPublications: [],
        books: [],
        invitedTalks: [],
        certifications: [],
        fdpAttended: [],
        fundedProjects: []
      };
      saveCurrentFaculty(blankFaculty);
      loadFacultyIntoForm(blankFaculty);
      switchView('form');
    }
  });

  // Form Section Buttons
  document.getElementById('btn-fill-sample')?.addEventListener('click', () => {
    loadFacultyIntoForm(SAMPLE_FACULTY_DR_RAMANATHAN);
    saveCurrentFaculty(SAMPLE_FACULTY_DR_RAMANATHAN);
    showToast("Populated all 13 parameters with Dr. K. Ramanathan sample data!");
  });

  document.getElementById('btn-expand-all')?.addEventListener('click', expandAllAccordions);
  document.getElementById('btn-collapse-all')?.addEventListener('click', collapseAllAccordions);

  // Add Another button triggers
  document.getElementById('btn-add-qualification')?.addEventListener('click', () => addQualificationRow());
  document.getElementById('btn-add-experience')?.addEventListener('click', () => addExperienceRow());
  document.getElementById('btn-add-membership')?.addEventListener('click', () => addMembershipRow());
  document.getElementById('btn-add-position')?.addEventListener('click', () => addPositionRow());
  document.getElementById('btn-add-achievement')?.addEventListener('click', () => addAchievementRow());
  document.getElementById('btn-add-consultancy')?.addEventListener('click', () => addConsultancyRow());
  document.getElementById('btn-add-journal')?.addEventListener('click', () => addJournalRow());
  document.getElementById('btn-add-book')?.addEventListener('click', () => addBookRow());
  document.getElementById('btn-add-talk')?.addEventListener('click', () => addTalkRow());
  document.getElementById('btn-add-certification')?.addEventListener('click', () => addCertificationRow());
  document.getElementById('btn-add-fdp')?.addEventListener('click', () => addFdpRow());
  document.getElementById('btn-add-funded-project')?.addEventListener('click', () => addFundedProjectRow());

  // Photo Upload & Preview
  const photoInput = document.getElementById('input-photo-file');
  photoInput?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
        showToast("Please select a valid JPG, JPEG, or PNG image file.", "error");
        return;
      }
      const reader = new FileReader();
      reader.onload = (evt) => {
        const preview = document.getElementById('form-photo-preview');
        if (preview) preview.src = evt.target.result;
        showToast("Profile photograph preview updated!");
      };
      reader.readAsDataURL(file);
    }
  });

  document.getElementById('btn-change-photo')?.addEventListener('click', () => {
    const sampleAvatars = [
      "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400",
      "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=400",
      "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=400",
      "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=400"
    ];
    const curSrc = document.getElementById('form-photo-preview').src;
    let nextIdx = (sampleAvatars.indexOf(curSrc) + 1) % sampleAvatars.length;
    document.getElementById('form-photo-preview').src = sampleAvatars[nextIdx];
    showToast("Sample academic portrait selected.");
  });

  document.getElementById('btn-remove-photo')?.addEventListener('click', () => {
    document.getElementById('form-photo-preview').src = 'assets/default-profile.png';
    showToast("Photo reset to default avatar.");
  });

  // Form Save / Preview / Submit
  document.getElementById('btn-save-draft')?.addEventListener('click', () => saveDraft(true));
  document.getElementById('btn-preview-profile')?.addEventListener('click', () => {
    const faculty = extractFacultyFromForm();
    saveCurrentFaculty(faculty);
    switchView('profile');
    showToast("Displaying profile preview.");
  });

  document.getElementById('faculty-profile-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const faculty = extractFacultyFromForm();
    if (!faculty.personalInfo?.fullName || !faculty.personalInfo?.employeeId || !faculty.personalInfo?.email) {
      showToast("Please fill in required fields: Full Name, Employee ID, and Email.", "error");
      return;
    }
    faculty.status = 'Published';
    saveCurrentFaculty(faculty);
    setAuthSession({
      role: 'faculty',
      facultyId: faculty.id,
      name: faculty.personalInfo.fullName,
      photo: faculty.personalInfo.photo
    });
    showToast("Profile successfully submitted & published!", "success");
    switchView('profile');
  });

  // Profile CV Action Buttons
  document.getElementById('cv-btn-dashboard')?.addEventListener('click', () => switchView('dashboard'));
  document.getElementById('cv-btn-edit')?.addEventListener('click', () => switchView('form'));
  document.getElementById('cv-btn-print')?.addEventListener('click', () => window.print());
  document.getElementById('cv-btn-pdf')?.addEventListener('click', () => window.print());

  // Authentication Modal Triggers
  document.getElementById('btn-open-login')?.addEventListener('click', () => {
    document.getElementById('modal-auth').classList.remove('hidden');
  });
  document.getElementById('modal-auth-close')?.addEventListener('click', () => {
    document.getElementById('modal-auth').classList.add('hidden');
  });
  document.getElementById('btn-logout')?.addEventListener('click', () => {
    setAuthSession({ role: 'guest', facultyId: null, name: 'Guest Visitor' });
    showToast("Logged out successfully.");
    switchView('landing');
  });

  // Modal Tabs Toggle
  document.getElementById('tab-btn-login')?.addEventListener('click', () => {
    document.getElementById('tab-btn-login').classList.add('active');
    document.getElementById('tab-btn-register').classList.remove('active');
    document.getElementById('form-auth-login').classList.remove('hidden');
    document.getElementById('form-auth-register').classList.add('hidden');
  });

  document.getElementById('tab-btn-register')?.addEventListener('click', () => {
    document.getElementById('tab-btn-register').classList.add('active');
    document.getElementById('tab-btn-login').classList.remove('active');
    document.getElementById('form-auth-register').classList.remove('hidden');
    document.getElementById('form-auth-login').classList.add('hidden');
  });

  // Login Submit
  document.getElementById('form-auth-login')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      showToast("Enter a valid official email address.", "error");
      return;
    }
    const roster = getFacultyRoster();
    const matched = roster.find(f => (f.personalInfo?.email || '').toLowerCase() === email.toLowerCase());

    if (!matched) {
      showToast("No faculty account is registered with this email. Please register first or contact the administrator.", "error");
      return;
    }

    const faculty = matched;
    saveCurrentFaculty(faculty);
    setAuthSession({
      role: 'faculty',
      facultyId: faculty.id,
      name: faculty.personalInfo.fullName,
      photo: faculty.personalInfo.photo
    });
    document.getElementById('modal-auth').classList.add('hidden');
    showToast(`Logged in as ${faculty.personalInfo.fullName}`);
    switchView('dashboard');
  });

  // Register Submit
  document.getElementById('form-auth-register')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const empid = document.getElementById('reg-empid').value.trim();
    const dept = document.getElementById('reg-dept').value;

    const newFaculty = {
      id: `ucen-${dept.slice(0, 3).toLowerCase()}-${Date.now().toString().slice(-4)}`,
      status: 'Draft',
      personalInfo: {
        fullName: name,
        designation: 'Assistant Professor',
        department: dept,
        employeeId: empid,
        email: email,
        contactNumber: '',
        doj: new Date().toISOString().split('T')[0],
        areasOfSpecialisation: [],
        biography: `${name} is an Assistant Professor in the Department of ${dept} at UCEN.`,
        photo: 'assets/default-profile.png'
      },
      qualifications: [],
      experience: [],
      memberships: [],
      positionsHeld: [],
      achievements: [],
      consultancy: [],
      journalPublications: [],
      books: [],
      invitedTalks: [],
      certifications: [],
      fdpAttended: [],
      fundedProjects: []
    };

    saveCurrentFaculty(newFaculty);
    setAuthSession({
      role: 'faculty',
      facultyId: newFaculty.id,
      name: newFaculty.personalInfo.fullName,
      photo: newFaculty.personalInfo.photo
    });

    document.getElementById('modal-auth').classList.add('hidden');
    showToast(`Account created for ${name}. Complete your 13 parameters!`);
    loadFacultyIntoForm(newFaculty);
    switchView('form');
  });

  // Quick helper inside modal
  document.getElementById('auth-quick-eval')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('login-email').value = 'ramanathan.k@ucen.ac.in';
    document.getElementById('login-password').value = 'annauniv@2024';
    showToast("Filled Dr. K. Ramanathan credentials.");
  });

  // Initial Form Load & View
  loadFacultyIntoForm(currentFaculty);
  switchView('landing');
});
