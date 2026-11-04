(function () {
  "use strict";

  document.documentElement.classList.add("js");

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

  const STORAGE_KEY = "ucen-faculty-portal-v2";
  const CURRENT_YEAR = new Date().getFullYear();
  const REDUCED_MOTION = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const DEPARTMENTS = [
    "Computer Science and Engineering",
    "Information Technology",
    "Electronics and Communication Engineering",
    "Electrical and Electronics Engineering",
    "Mechanical Engineering",
    "Civil Engineering",
    "MBA",
    "Science and Humanities"
  ];

  const DESIGNATIONS = [
    "Professor & Head",
    "Professor",
    "Associate Professor",
    "Assistant Professor & HOD",
    "Assistant Professor",
    "Dean",
    "Visiting Faculty"
  ];

  const AVATAR_GRADIENTS = [
    ["#7B5EF0", "#B39CFF"],
    ["#4C6FFF", "#8FB2FF"],
    ["#0E9F9F", "#6EE7D8"],
    ["#D97706", "#FCD34D"],
    ["#DB2777", "#F9A8D4"],
    ["#059669", "#6EE7B7"],
    ["#7C3AED", "#C4B5FD"],
    ["#0F766E", "#5EEAD4"]
  ];

  const SAMPLE_OWNER = {
    id: "fac-ramanathan",
    name: "Dr. K. Ramanathan",
    facultyId: "UCEN-CSE-1042",
    department: "Computer Science and Engineering",
    designation: "Professor & Head",
    email: "radhakrishnan1974@ucen.ac.in",
    contact: "+91 94432 18765",
    specialization: "Artificial Intelligence, Cloud Computing, Data Mining",
    bio: "Professor and Head of the Department of Computer Science and Engineering at UCEN, with more than two decades of teaching and research experience. My work focuses on applied artificial intelligence, secure cloud systems and data-driven solutions for agriculture and education.",
    photo: "",
    qualifications: [
      {
        degree: "Ph.D. in Computer Science and Engineering",
        institution: "National Institute of Technology, Tiruchirappalli",
        university: "Anna University, Chennai",
        year: "2009",
        specialization: "Artificial Intelligence"
      },
      {
        degree: "M.E. in Computer Science and Engineering",
        institution: "College of Engineering, Guindy",
        university: "Anna University, Chennai",
        year: "2003",
        specialization: "Software Engineering"
      },
      {
        degree: "B.E. in Computer Science and Engineering",
        institution: "Thiagarajar College of Engineering, Madurai",
        university: "Madurai Kamaraj University",
        year: "2000",
        specialization: "Computer Science"
      }
    ],
    experience: [
      {
        institution: "University College of Engineering Nagercoil",
        designation: "Professor & Head",
        from: "2018-06",
        to: "",
        description: "Leading the Department of Computer Science and Engineering, coordinating academic programmes, research initiatives and industry collaborations."
      },
      {
        institution: "University College of Engineering Nagercoil",
        designation: "Professor",
        from: "2013-07",
        to: "2018-05",
        description: "Taught core and elective courses in artificial intelligence, cloud computing and data mining, and supervised doctoral research."
      },
      {
        institution: "Sree Sastha Institute of Engineering and Technology, Chennai",
        designation: "Associate Professor",
        from: "2007-06",
        to: "2013-06",
        description: "Established the departmental research lab and guided student projects in intelligent systems and web technologies."
      }
    ],
    projects: [
      {
        title: "AI-Assisted Early Detection of Crop Disease for Smallholder Farmers",
        agency: "Science and Engineering Research Board (DST)",
        role: "Principal Investigator",
        duration: "2022 – 2025",
        amount: "4850000",
        status: "Ongoing",
        description: "A field-deployable computer vision system that helps farmers identify crop disease early using low-cost mobile devices and regional-language alerts."
      },
      {
        title: "Secure Cloud Framework for Academic Record Management",
        agency: "AICTE – Research Promotion Scheme",
        role: "Co-Investigator",
        duration: "2019 – 2022",
        amount: "1850000",
        status: "Completed",
        description: "Designed an encrypted, audit-ready cloud architecture for inter-collegiate academic record exchange."
      },
      {
        title: "Intelligent Tutoring System for Regional Language Learners",
        agency: "Tamil Nadu State Council for Science and Technology",
        role: "Principal Investigator",
        duration: "2024 – 2026",
        amount: "950000",
        status: "Approved",
        description: "Adaptive learning platform that personalises practice material for first-generation learners in Tamil-medium schools."
      }
    ],
    publications: [
      {
        title: "Explainable Deep Learning for Crop Disease Identification in Resource-Constrained Environments",
        venue: "IEEE Transactions on Emerging Topics in Computing",
        year: "2024",
        link: "https://doi.org/10.1109/TETC.2024.10421873"
      },
      {
        title: "A Privacy-Preserving Architecture for Academic Credential Exchange",
        venue: "Journal of Information Security and Applications",
        year: "2023",
        link: "https://doi.org/10.1016/j.jisa.2023.103562"
      },
      {
        title: "Adaptive Learning Paths for Regional Language Classrooms",
        venue: "International Conference on Advanced Computing (ICoAC)",
        year: "2022",
        link: ""
      },
      {
        title: "Energy-Aware Scheduling for Federated Cloud Workloads",
        venue: "Cluster Computing",
        year: "2021",
        link: "https://doi.org/10.1007/s10586-021-03245-8"
      }
    ],
    achievements: [
      {
        title: "Best Faculty Researcher Award",
        organization: "Anna University, Chennai",
        year: "2023",
        description: "Recognised for sustained research output and externally funded projects in applied artificial intelligence."
      },
      {
        title: "Outstanding Contribution to Teaching",
        organization: "University College of Engineering Nagercoil",
        year: "2021",
        description: "Awarded for curriculum innovation and mentorship of student research teams."
      },
      {
        title: "Patent Granted – Context-Aware Irrigation Advisory System",
        organization: "Intellectual Property India",
        year: "2022",
        description: "A smart irrigation method that combines soil sensor data with local weather forecasts to reduce water usage."
      }
    ],
    certifications: [
      {
        title: "AWS Certified Solutions Architect – Associate",
        organization: "Amazon Web Services",
        year: "2024",
        description: "Professional certification covering secure, scalable cloud architecture design."
      },
      {
        title: "NPTEL Certification in Deep Learning",
        organization: "IIT Madras",
        year: "2023",
        description: "Completed the advanced NPTEL programme with elite distinction."
      },
      {
        title: "Certified Data Science Professional",
        organization: "IBM",
        year: "2022",
        description: "Credential focused on the end-to-end data science workflow and model deployment."
      }
    ]
  };

  const SAMPLE_FACULTY = [
    {
      id: "fac-anitha",
      name: "Dr. M. Anitha",
      facultyId: "UCEN-ECE-2018",
      department: "Electronics and Communication Engineering",
      designation: "Associate Professor",
      email: "m.anitha@ucen.ac.in",
      contact: "+91 98421 33672",
      specialization: "VLSI Design, Embedded Systems, Signal Processing",
      bio: "Associate Professor in Electronics and Communication Engineering, working on low-power integrated circuits and embedded systems for healthcare and agricultural sensing.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Electronics Engineering", institution: "College of Engineering, Guindy", university: "Anna University, Chennai", year: "2014", specialization: "VLSI Design" },
        { degree: "M.E. in Applied Electronics", institution: "PSG College of Technology, Coimbatore", university: "Anna University, Chennai", year: "2010", specialization: "Embedded Systems" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Associate Professor", from: "2019-07", to: "", description: "Coordinating the VLSI and embedded systems laboratories and supervising postgraduate research projects." },
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2012-06", to: "2019-06", description: "Taught digital electronics, VLSI design and signal processing courses." }
      ],
      projects: [
        { title: "Low-Power VLSI Architectures for Wearable Health Monitors", agency: "Science and Engineering Research Board (DST)", role: "Co-Investigator", duration: "2021 – 2024", amount: "1250000", status: "Completed", description: "Developed energy-efficient integrated circuits for continuous vital-sign monitoring in wearable devices." }
      ],
      publications: [
        { title: "Sub-Threshold SRAM Design for Wearable Biomedical Sensors", venue: "Microelectronics Journal", year: "2023", link: "https://doi.org/10.1016/j.mejo.2023.105622" },
        { title: "A Reconfigurable FIR Filter for Low-Power Acoustic Sensing", venue: "IEEE International Conference on VLSI Design", year: "2022", link: "" }
      ],
      achievements: [
        { title: "Best Paper Award – IEEE VLSI Design Conference", organization: "IEEE VLSI Society", year: "2022", description: "Awarded for a paper on energy-efficient filtering architectures." }
      ],
      certifications: [
        { title: "NPTEL Certification in VLSI Design", organization: "IIT Bombay", year: "2023", description: "Completed the advanced VLSI design programme with distinction." },
        { title: "Cadence Certified Digital Design Professional", organization: "Cadence Design Systems", year: "2021", description: "Professional credential in digital IC design and verification." }
      ]
    },
    {
      id: "fac-priya",
      name: "Dr. G. Priya",
      facultyId: "UCEN-MBA-1007",
      department: "MBA",
      designation: "Professor",
      email: "g.priya@ucen.ac.in",
      contact: "+91 98405 71234",
      specialization: "Organisational Behaviour, Human Resource Management, Entrepreneurship",
      bio: "Professor of Management Studies with a focus on organisational behaviour, women's entrepreneurship and leadership development in higher education.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Management", institution: "Bharathiar University, Coimbatore", university: "Bharathiar University", year: "2012", specialization: "Organisational Behaviour" },
        { degree: "MBA in Human Resource Management", institution: "Madurai Kamaraj University", university: "Madurai Kamaraj University", year: "2005", specialization: "Human Resource Management" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Professor", from: "2019-06", to: "", description: "Leading management research, doctoral supervision and industry engagement for the Department of Management Studies." },
        { institution: "University College of Engineering Nagercoil", designation: "Associate Professor", from: "2012-06", to: "2019-05", description: "Designed and delivered courses in organisational behaviour and human resource management." }
      ],
      projects: [
        { title: "Women Entrepreneurship Incubation Programme for Southern Tamil Nadu", agency: "AICTE – Entrepreneurship Development Cell", role: "Project Coordinator", duration: "2020 – 2023", amount: "800000", status: "Completed", description: "Mentored 120 women-led enterprises through structured incubation, financial literacy and market-linkage support." }
      ],
      publications: [
        { title: "Workplace Flexibility and Employee Well-Being in Higher Education", venue: "South Asian Journal of Human Resources Management", year: "2024", link: "https://doi.org/10.1177/2322093724123456" },
        { title: "Building Entrepreneurial Intent among Rural Women", venue: "Journal of Entrepreneurship and Innovation in Emerging Economies", year: "2022", link: "" }
      ],
      achievements: [
        { title: "Excellence in Management Education Award", organization: "Indian Management Association", year: "2023", description: "Recognised for contributions to inclusive entrepreneurship education." }
      ],
      certifications: [
        { title: "Certified Professional in Talent Development", organization: "ATD", year: "2022", description: "Credential covering learning strategy and talent development." },
        { title: "NPTEL Certification in Leadership and Team Effectiveness", organization: "IIT Roorkee", year: "2021", description: "Advanced programme in leadership practice and team dynamics." }
      ]
    },
    {
      id: "fac-arunachalam",
      name: "Dr. S. Arunachalam",
      facultyId: "UCEN-MECH-3061",
      department: "Mechanical Engineering",
      designation: "Assistant Professor",
      email: "s.arunachalam@ucen.ac.in",
      contact: "+91 97863 44210",
      specialization: "Thermal Engineering, Renewable Energy, Computational Fluid Dynamics",
      bio: "Assistant Professor researching solar thermal systems, energy storage and computational modelling of heat transfer in sustainable engineering applications.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Mechanical Engineering", institution: "Indian Institute of Technology Madras", university: "IIT Madras", year: "2016", specialization: "Thermal Engineering" },
        { degree: "M.E. in Thermal Engineering", institution: "Government College of Technology, Coimbatore", university: "Anna University, Chennai", year: "2010", specialization: "Energy Systems" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2017-07", to: "", description: "Teaching thermodynamics, heat transfer and CFD, and leading the renewable energy research group." },
        { institution: "Sri Venkateswara College of Engineering, Chennai", designation: "Assistant Professor", from: "2010-06", to: "2017-06", description: "Delivered undergraduate courses and supported thermal engineering laboratories." }
      ],
      projects: [
        { title: "Solar Thermal Hybrid Dryer for Small-Scale Marine Food Processing", agency: "Science and Engineering Research Board (DST)", role: "Principal Investigator", duration: "2023 – 2026", amount: "2200000", status: "Ongoing", description: "Designing a hybrid solar dryer to improve shelf life and quality for coastal food-processing enterprises." }
      ],
      publications: [
        { title: "Performance of a Phase-Change Energy Storage Unit for Solar Dryers", venue: "Solar Energy", year: "2024", link: "https://doi.org/10.1016/j.solener.2024.112865" },
        { title: "CFD Analysis of Jet Impingement Cooling in Electronic Enclosures", venue: "Applied Thermal Engineering", year: "2022", link: "" }
      ],
      achievements: [
        { title: "Young Researcher Award", organization: "Indian Society for Heat and Mass Transfer", year: "2023", description: "Awarded for research on thermal energy storage for decentralised solar applications." }
      ],
      certifications: [
        { title: "Certified Energy Auditor", organization: "Bureau of Energy Efficiency, Government of India", year: "2022", description: "Certified to conduct industrial and commercial energy audits." },
        { title: "NPTEL Certification in Computational Fluid Dynamics", organization: "IIT Kanpur", year: "2021", description: "Advanced course covering finite-volume methods and turbulence modelling." }
      ]
    },
    {
      id: "fac-meenakshi",
      name: "Dr. R. Meenakshi",
      facultyId: "UCEN-IT-2088",
      department: "Information Technology",
      designation: "Assistant Professor",
      email: "r.meenakshi@ucen.ac.in",
      contact: "+91 94431 50826",
      specialization: "Data Science, Machine Learning, Natural Language Processing",
      bio: "Assistant Professor in Information Technology, developing machine learning and language technologies for healthcare, education and public-service applications in Tamil.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Information Technology", institution: "College of Engineering, Guindy", university: "Anna University, Chennai", year: "2018", specialization: "Machine Learning" },
        { degree: "M.Tech. in Information Technology", institution: "SSN College of Engineering, Chennai", university: "Anna University, Chennai", year: "2011", specialization: "Data Science" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2018-07", to: "", description: "Teaching data science and machine learning, and leading the Tamil language technologies research initiative." },
        { institution: "SRM Institute of Science and Technology, Chennai", designation: "Assistant Professor", from: "2011-06", to: "2018-06", description: "Handled courses in database systems and artificial intelligence." }
      ],
      projects: [
        { title: "Tamil Natural Language Processing Toolkit for Primary Healthcare", agency: "Science and Engineering Research Board (DST)", role: "Principal Investigator", duration: "2022 – 2025", amount: "1650000", status: "Ongoing", description: "Building open linguistic resources and models that help frontline health workers access information in Tamil." }
      ],
      publications: [
        { title: "Low-Resource Tamil Named Entity Recognition with Transfer Learning", venue: "ACM Transactions on Asian and Low-Resource Language Information Processing", year: "2024", link: "https://doi.org/10.1145/3648421" },
        { title: "Explainable Machine Learning for Student Performance Prediction", venue: "Education and Information Technologies", year: "2023", link: "" }
      ],
      achievements: [
        { title: "Women in Data Science Research Award", organization: "WiDS Chennai", year: "2024", description: "Recognised for contributions to low-resource language processing and inclusive AI." }
      ],
      certifications: [
        { title: "TensorFlow Developer Certificate", organization: "Google", year: "2023", description: "Professional certification in building and deploying machine learning models." },
        { title: "NPTEL Certification in Natural Language Processing", organization: "IIT Kharagpur", year: "2022", description: "Advanced programme covering statistical and neural language processing." }
      ]
    },
    {
      id: "fac-vignesh",
      name: "Dr. P. Vignesh",
      facultyId: "UCEN-CIVIL-3024",
      department: "Civil Engineering",
      designation: "Associate Professor",
      email: "p.vignesh@ucen.ac.in",
      contact: "+91 99445 67218",
      specialization: "Structural Engineering, Earthquake Resistant Design, Concrete Technology",
      bio: "Associate Professor of Civil Engineering working on resilient structures, sustainable concrete and seismic safety for public infrastructure in coastal Tamil Nadu.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Civil Engineering", institution: "National Institute of Technology Karnataka, Surathkal", university: "NITK Surathkal", year: "2015", specialization: "Structural Engineering" },
        { degree: "M.E. in Structural Engineering", institution: "Thiagarajar College of Engineering, Madurai", university: "Anna University, Chennai", year: "2009", specialization: "Structural Engineering" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Associate Professor", from: "2018-06", to: "", description: "Leading the structural engineering laboratory and coordinating industry consultancy projects." },
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2009-07", to: "2018-05", description: "Taught structural analysis, reinforced concrete design and earthquake engineering." }
      ],
      projects: [
        { title: "Seismic Performance Assessment of Coastal School Buildings", agency: "AICTE – Research Promotion Scheme", role: "Principal Investigator", duration: "2020 – 2023", amount: "1400000", status: "Completed", description: "Assessed vulnerability and developed low-cost retrofit solutions for school buildings in coastal districts." }
      ],
      publications: [
        { title: "Retrofit Strategies for Reinforced Concrete School Buildings in Coastal Regions", venue: "Journal of Building Engineering", year: "2023", link: "https://doi.org/10.1016/j.jobe.2023.106842" },
        { title: "Durability of Sustainable Concrete with Industrial By-Products", venue: "Construction and Building Materials", year: "2021", link: "" }
      ],
      achievements: [
        { title: "Outstanding Engineering Consultancy Award", organization: "Institution of Engineers (India)", year: "2022", description: "Recognised for consultancy work on safe and sustainable public buildings." }
      ],
      certifications: [
        { title: "Certified Structural Steel Designer", organization: "INSTRUCT", year: "2021", description: "Professional certification in structural steel design." },
        { title: "NPTEL Certification in Earthquake Resistant Design", organization: "IIT Madras", year: "2020", description: "Advanced programme on seismic design of buildings." }
      ]
    },
    {
      id: "fac-sharmila",
      name: "Dr. L. Sharmila",
      facultyId: "UCEN-SH-4012",
      department: "Science and Humanities",
      designation: "Assistant Professor",
      email: "l.sharmila@ucen.ac.in",
      contact: "+91 95001 47853",
      specialization: "Applied Mathematics, Numerical Analysis, Operations Research",
      bio: "Assistant Professor of Mathematics applying numerical methods and optimisation models to engineering design, supply chains and public health planning.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Mathematics", institution: "Madurai Kamaraj University", university: "Madurai Kamaraj University", year: "2017", specialization: "Applied Mathematics" },
        { degree: "M.Sc. in Mathematics", institution: "Lady Doak College, Madurai", university: "Madurai Kamaraj University", year: "2008", specialization: "Numerical Analysis" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2016-07", to: "", description: "Teaching engineering mathematics and supervising interdisciplinary modelling projects." },
        { institution: "St. Xavier's College, Palayamkottai", designation: "Assistant Professor", from: "2009-06", to: "2016-06", description: "Taught undergraduate and postgraduate mathematics." }
      ],
      projects: [
        { title: "Mathematical Modelling of Dengue Transmission in Coastal Districts", agency: "Tamil Nadu State Council for Science and Technology", role: "Principal Investigator", duration: "2023 – 2025", amount: "600000", status: "Ongoing", description: "Developing predictive models to support vector-control planning and public health resource allocation." }
      ],
      publications: [
        { title: "A Fractional-Order Model for Dengue Dynamics with Seasonal Forcing", venue: "Mathematics and Computers in Simulation", year: "2024", link: "https://doi.org/10.1016/j.matcom.2024.104215" },
        { title: "Optimisation of Supply Chains with Uncertain Demand", venue: "Opsearch", year: "2022", link: "" }
      ],
      achievements: [
        { title: "Best Young Mathematician Award", organization: "Indian Mathematical Society", year: "2023", description: "Awarded for work on fractional-order epidemic models." }
      ],
      certifications: [
        { title: "NPTEL Certification in Operations Research", organization: "IIT Madras", year: "2022", description: "Advanced programme in linear and integer programming." },
        { title: "Python for Scientific Computing", organization: "Coursera", year: "2021", description: "Professional certificate in numerical computing with Python." }
      ]
    },
    {
      id: "fac-jebasingh",
      name: "Dr. A. Jebasingh",
      facultyId: "UCEN-EEE-1055",
      department: "Electrical and Electronics Engineering",
      designation: "Professor",
      email: "a.jebasingh@ucen.ac.in",
      contact: "+91 94433 62901",
      specialization: "Power Systems, Smart Grid, Power Electronics",
      bio: "Professor of Electrical and Electronics Engineering researching smart grid control, renewable integration and power electronic converters for reliable energy delivery.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Electrical Engineering", institution: "Indian Institute of Technology Delhi", university: "IIT Delhi", year: "2011", specialization: "Power Systems" },
        { degree: "M.E. in Power Systems Engineering", institution: "College of Engineering, Guindy", university: "Anna University, Chennai", year: "2004", specialization: "Power Systems" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Professor", from: "2018-07", to: "", description: "Leading research in smart grid control and coordinating the power systems laboratory." },
        { institution: "University College of Engineering Nagercoil", designation: "Associate Professor", from: "2011-06", to: "2018-06", description: "Delivered courses in power systems, power electronics and renewable energy." }
      ],
      projects: [
        { title: "Smart Grid Testbed for Distributed Solar Integration in Southern Tamil Nadu", agency: "Department of Science and Technology", role: "Principal Investigator", duration: "2021 – 2024", amount: "3200000", status: "Completed", description: "Built a laboratory testbed for studying voltage stability and coordinated control under high solar penetration." }
      ],
      publications: [
        { title: "Coordinated Voltage Control for Distributed Solar PV in Low-Voltage Networks", venue: "IEEE Transactions on Smart Grid", year: "2024", link: "https://doi.org/10.1109/TSG.2024.3387124" },
        { title: "Model Predictive Control of a Bidirectional Converter", venue: "IEEE Transactions on Power Electronics", year: "2022", link: "" }
      ],
      achievements: [
        { title: "Fellow of the Institution of Engineers", organization: "Institution of Engineers (India)", year: "2023", description: "Elected Fellow for contributions to power system research and education." }
      ],
      certifications: [
        { title: "Certified Power System Protection Engineer", organization: "ABB University", year: "2022", description: "Advanced certification in protection coordination and relay settings." },
        { title: "NPTEL Certification in Smart Grid Technology", organization: "IIT Kharagpur", year: "2021", description: "Programme covering smart grid architecture, communication and control." }
      ]
    },
    {
      id: "fac-kavitha",
      name: "Dr. N. Kavitha",
      facultyId: "UCEN-CSE-2033",
      department: "Computer Science and Engineering",
      designation: "Associate Professor",
      email: "n.kavitha@ucen.ac.in",
      contact: "+91 90031 78426",
      specialization: "Cyber Security, Blockchain, Internet of Things",
      bio: "Associate Professor specialising in cyber security, distributed ledger technology and secure IoT systems for critical public infrastructure.",
      photo: "",
      qualifications: [
        { degree: "Ph.D. in Computer Science and Engineering", institution: "College of Engineering, Guindy", university: "Anna University, Chennai", year: "2016", specialization: "Cyber Security" },
        { degree: "M.E. in Computer Science and Engineering", institution: "Government College of Engineering, Tirunelveli", university: "Anna University, Chennai", year: "2009", specialization: "Information Security" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Associate Professor", from: "2020-06", to: "", description: "Coordinating cyber security research and the secure systems laboratory." },
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2010-07", to: "2020-05", description: "Taught computer networks, cryptography and information security." }
      ],
      projects: [
        { title: "Blockchain-Based Tamper-Proof Land Record Management for Rural Administration", agency: "Ministry of Electronics and Information Technology (MeitY)", role: "Principal Investigator", duration: "2022 – 2025", amount: "2500000", status: "Ongoing", description: "Prototyping a permissioned blockchain platform for transparent and auditable land records in village administration." }
      ],
      publications: [
        { title: "A Permissioned Blockchain Architecture for Public Land Records", venue: "Journal of Network and Computer Applications", year: "2024", link: "https://doi.org/10.1016/j.jnca.2024.103928" },
        { title: "Lightweight Authentication for Resource-Constrained IoT Devices", venue: "Ad Hoc Networks", year: "2023", link: "" }
      ],
      achievements: [
        { title: "Cyber Security Educator of the Year", organization: "Cyber Society of India", year: "2023", description: "Recognised for building a strong undergraduate security research pipeline." }
      ],
      certifications: [
        { title: "Certified Information Systems Security Professional (CISSP)", organization: "ISC²", year: "2022", description: "Professional certification in information security governance and architecture." },
        { title: "NPTEL Certification in Blockchain Technology", organization: "IIT Madras", year: "2021", description: "Advanced programme on distributed ledgers and consensus mechanisms." }
      ]
    },
    {
      id: "fac-ananya",
      name: "Dr. Ananya Nair",
      facultyId: "UCEN-IT-2110",
      department: "Information Technology",
      designation: "Assistant Professor",
      email: "",
      contact: "",
      specialization: "Human-Computer Interaction, Learning Analytics",
      bio: "New faculty member exploring inclusive learning technologies and analytics for higher education.",
      photo: "",
      qualifications: [],
      experience: [],
      projects: [],
      publications: [],
      achievements: [],
      certifications: []
    },
    {
      id: "fac-vishal",
      name: "Mr. Vishal Menon",
      facultyId: "UCEN-ECE-2118",
      department: "Electronics and Communication Engineering",
      designation: "Assistant Professor",
      email: "v.menon@ucen.ac.in",
      contact: "+91 90032 55718",
      specialization: "VLSI Design, Embedded Systems",
      bio: "", 
      photo: "",
      qualifications: [
        { degree: "M.E. in VLSI Design", institution: "College of Engineering, Guindy", university: "Anna University, Chennai", year: "2021", specialization: "VLSI Design" }
      ],
      experience: [
        { institution: "University College of Engineering Nagercoil", designation: "Assistant Professor", from: "2022-07", to: "", description: "Teaching digital design, embedded systems and VLSI laboratory courses." }
      ],
      projects: [],
      publications: [],
      achievements: [],
      certifications: []
    }
  ];

  const ENTRY_CONFIG = {
    qualification: {
      label: "Qualification",
      container: "entry-list-qualification",
      fields: [
        { key: "degree", label: "Degree", placeholder: "Ph.D. in Computer Science", required: true, full: true },
        { key: "institution", label: "Institution name", placeholder: "National Institute of Technology", required: true },
        { key: "university", label: "University", placeholder: "Anna University, Chennai" },
        { key: "year", label: "Year of completion", type: "number", placeholder: "2018", required: true, min: 1950, max: CURRENT_YEAR + 1 },
        { key: "specialization", label: "Specialisation", placeholder: "Artificial Intelligence" }
      ]
    },
    experience: {
      label: "Experience",
      container: "entry-list-experience",
      fields: [
        { key: "institution", label: "Institution name", placeholder: "University College of Engineering Nagercoil", required: true, full: true },
        { key: "designation", label: "Designation", placeholder: "Associate Professor", required: true },
        { key: "from", label: "From date", type: "month", required: true },
        { key: "to", label: "To date", type: "month" },
        { key: "total", label: "Total experience", placeholder: "6 years 4 months" },
        { key: "description", label: "Experience description", type: "textarea", placeholder: "Describe your responsibilities and key contributions.", full: true }
      ]
    },
    project: {
      label: "Funded project",
      container: "entry-list-project",
      fields: [
        { key: "title", label: "Project title", placeholder: "AI-Assisted Crop Disease Detection", required: true, full: true },
        { key: "agency", label: "Funding agency", placeholder: "Science and Engineering Research Board (DST)", required: true },
        { key: "role", label: "Role", placeholder: "Principal Investigator" },
        { key: "duration", label: "Duration", placeholder: "2023 – 2026" },
        { key: "amount", label: "Sanctioned amount (₹)", type: "number", placeholder: "2500000", min: 0 },
        { key: "status", label: "Project status", type: "select", options: ["Proposed", "Approved", "Ongoing", "Completed"], required: true },
        { key: "description", label: "Description", type: "textarea", placeholder: "Briefly describe the project objectives and outcomes.", full: true }
      ]
    },
    publication: {
      label: "Publication",
      container: "entry-list-publication",
      fields: [
        { key: "title", label: "Publication title", placeholder: "Paper title", required: true, full: true },
        { key: "venue", label: "Journal / Conference", placeholder: "IEEE Transactions on ...", required: true },
        { key: "year", label: "Publication year", type: "number", placeholder: "2024", required: true, min: 1950, max: CURRENT_YEAR + 1 },
        { key: "type", label: "Publication type", type: "select", options: ["Journal Article", "Conference Paper", "Book Chapter", "Book", "Patent", "Other"], required: true },
        { key: "link", label: "DOI / Link", type: "url", placeholder: "https://doi.org/...", full: true }
      ]
    },
    achievement: {
      label: "Achievement",
      container: "entry-list-achievement",
      fields: [
        { key: "title", label: "Achievement title", placeholder: "Best Faculty Researcher Award", required: true, full: true },
        { key: "organization", label: "Issuing organisation", placeholder: "Anna University, Chennai", required: true },
        { key: "year", label: "Year", type: "number", placeholder: "2023", required: true, min: 1950, max: CURRENT_YEAR + 1 },
        { key: "description", label: "Description", type: "textarea", placeholder: "Briefly describe the achievement.", full: true }
      ]
    },
    certification: {
      label: "Certification",
      container: "entry-list-certification",
      fields: [
        { key: "title", label: "Certification title", placeholder: "AWS Certified Solutions Architect", required: true, full: true },
        { key: "organization", label: "Issuing organisation", placeholder: "Amazon Web Services", required: true },
        { key: "year", label: "Year", type: "number", placeholder: "2024", required: true, min: 1950, max: CURRENT_YEAR + 1 },
        { key: "description", label: "Description", type: "textarea", placeholder: "Add credential details or a short description.", full: true }
      ]
    }
  };

  const ROLES = {
    admin: { label: "Admin / In-charge", short: "Admin", icon: "bi-shield-lock-fill" },
    faculty: { label: "Faculty Member", short: "Faculty", icon: "bi-person-badge-fill" },
    viewer: { label: "General Viewer", short: "Viewer", icon: "bi-eye-fill" }
  };

  const state = {
    role: "faculty",
    roster: [],
    activeFacultyId: "",
    session: false,
    view: "dashboard",
    viewed: null,
    draftPhoto: "",
    formId: "",
    formIsNew: false,
    preview: null,
    sidebarExpanded: false
  };

  let entryUid = 0;
  let revealObserver = null;
  let lastFocused = null;
  let gateLastFocused = null;
  let sidebarHovering = false;

  const SECTION_LABELS = {
    personal: "Personal Information",
    qualification: "Qualification",
    experience: "Professional Experience",
    projects: "Funded Projects",
    publications: "Publications",
    achievements: "Achievements",
    certifications: "Certifications"
  };

  const SHORT_DEPARTMENTS = {
    "Computer Science and Engineering": "CSE",
    "Information Technology": "IT",
    "Electronics and Communication Engineering": "ECE",
    "Electrical and Electronics Engineering": "EEE",
    "Mechanical Engineering": "MECH",
    "Civil Engineering": "CIVIL",
    "MBA": "MBA",
    "Science and Humanities": "S&H"
  };

  function escapeHTML(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function hashString(value) {
    let hash = 0;
    const text = String(value || "");
    for (let i = 0; i < text.length; i += 1) {
      hash = (hash * 31 + text.charCodeAt(i)) % 2147483647;
    }
    return hash;
  }

  function initialsOf(name) {
    const parts = String(name || "Faculty Member")
      .replace(/^(Dr|Prof|Mr|Ms|Mrs)\.?\s+/i, "")
      .split(/\s+/)
      .filter(Boolean);
    if (!parts.length) return "FM";
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }

  function gradientFor(name) {
    return AVATAR_GRADIENTS[hashString(name) % AVATAR_GRADIENTS.length];
  }

  function avatarHTML(profile, extraClass) {
    const name = profile && profile.name ? profile.name : "Faculty Member";
    const photo = profile && profile.photo ? profile.photo : "";
    const className = "avatar" + (extraClass ? " " + extraClass : "");
    if (photo) {
      return `<div class="${className}"><img src="${escapeHTML(photo)}" alt="${escapeHTML(name)}" loading="lazy"></div>`;
    }
    const gradient = gradientFor(name);
    return `<div class="${className} avatar--initials" style="--av-a:${gradient[0]};--av-b:${gradient[1]}"><span aria-hidden="true">${escapeHTML(initialsOf(name))}</span><span class="sr-only">${escapeHTML(name)}</span></div>`;
  }

  function departmentShort(department) {
    return SHORT_DEPARTMENTS[department] || String(department || "UCEN").split(/\s+/).map(function (word) { return word[0]; }).join("").slice(0, 4).toUpperCase();
  }

  function formatAmount(value) {
    const amount = Number(value);
    if (!value || Number.isNaN(amount) || amount <= 0) return "";
    if (amount >= 10000000) return "₹" + (amount / 10000000).toFixed(amount % 10000000 === 0 ? 0 : 1) + " Cr";
    if (amount >= 100000) return "₹" + (amount / 100000).toFixed(amount % 100000 === 0 ? 0 : 1) + " L";
    return "₹" + amount.toLocaleString("en-IN");
  }

  function formatMonth(value) {
    if (!value) return "Present";
    const parts = String(value).split("-");
    const year = Number(parts[0]);
    const month = Number(parts[1] || 1);
    if (!year) return escapeHTML(value);
    const formatter = new Intl.DateTimeFormat("en-IN", { month: "short", year: "numeric" });
    const date = new Date(year, Math.max(0, Math.min(11, month - 1)), 1);
    return formatter.format(date);
  }

  function dateRange(from, to) {
    if (!from && !to) return "";
    return formatMonth(from) + " – " + formatMonth(to);
  }

  function externalUrl(value) {
    const url = String(value || "").trim();
    if (/^https?:\/\//i.test(url)) return url;
    if (/^10\.\d{4,9}\//.test(url)) return "https://doi.org/" + url;
    return "";
  }

  function safeText(value, fallback) {
    const text = String(value == null ? "" : value).trim();
    return text || fallback || "";
  }

  function normalizeDepartment(value) {
    const department = safeText(value);
    return /^management studies/i.test(department) ? "MBA" : department;
  }

  function normalizeFacultyEmail(value) {
    const email = safeText(value);
    return email.toLowerCase() === "k.ramanathan@ucen.ac.in" ? "radhakrishnan1974@ucen.ac.in" : email;
  }

  function countItems(list) {
    if (!Array.isArray(list)) return 0;
    return list.filter(function (item) {
      return item && Object.keys(item).some(function (key) {
        return String(item[key] == null ? "" : item[key]).trim() !== "";
      });
    }).length;
  }

  function personalComplete(profile) {
    if (!profile) return false;
    return ["name", "facultyId", "department", "designation", "email", "contact", "specialization"].every(function (key) {
      return String(profile[key] || "").trim() !== "";
    });
  }

  function sectionComplete(profile, key) {
    if (!profile) return false;
    if (key === "personal") return personalComplete(profile);
    return countItems(profile[key]) > 0;
  }

  function computeCompletion(profile) {
    const keys = ["personal", "qualification", "experience", "projects", "publications", "achievements", "certifications"];
    const sections = keys.map(function (key) {
      return { key: key, label: SECTION_LABELS[key], complete: sectionComplete(profile, key) };
    });
    const filled = sections.filter(function (item) { return item.complete; }).length;
    return {
      percent: Math.round((filled / keys.length) * 100),
      filled: filled,
      total: keys.length,
      sections: sections
    };
  }

  function completionBadge(percent) {
    if (percent >= 100) return { label: "Profile complete", tone: "complete" };
    if (percent >= 70) return { label: "Looking strong", tone: "strong" };
    if (percent >= 40) return { label: "In progress", tone: "progress" };
    return { label: "Getting started", tone: "start" };
  }

  function normalizeEntry(entry, type) {
    const config = ENTRY_CONFIG[type];
    const clean = {};
    config.fields.forEach(function (field) {
      clean[field.key] = entry && entry[field.key] != null ? String(entry[field.key]) : "";
    });
    if (type === "publication" && !clean.type) {
      const venue = [clean.venue, clean.title].filter(Boolean).join(" ");
      clean.type = /conference|proceedings|workshop|symposium/i.test(venue) ? "Conference Paper" : "Journal Article";
    }
    return clean;
  }

  function normalizeProfile(raw, fallbackName) {
    const source = raw && typeof raw === "object" ? raw : {};
    const profile = {
      id: safeText(source.id, "fac-owner"),
      name: safeText(source.name, fallbackName || "Faculty Member"),
      facultyId: safeText(source.facultyId),
        department: normalizeDepartment(source.department),
        designation: safeText(source.designation),
        email: normalizeFacultyEmail(source.email),
      contact: safeText(source.contact),
      specialization: safeText(source.specialization),
      bio: safeText(source.bio),
      photo: safeText(source.photo)
    };
    const aliases = {
      qualification: "qualifications",
      experience: "experience",
      project: "projects",
      publication: "publications",
      achievement: "achievements",
      certification: "certifications"
    };
    Object.keys(ENTRY_CONFIG).forEach(function (type) {
      const rawList = Array.isArray(source[type])
        ? source[type]
        : Array.isArray(source[aliases[type]]) ? source[aliases[type]] : [];
      const list = rawList;
      profile[type] = list
        .map(function (entry) { return normalizeEntry(entry, type); })
        .filter(function (entry) {
          return Object.keys(entry).some(function (key) { return entry[key] !== ""; });
        });
    });
    return applySectionAliases(profile);
  }

  const SECTION_ALIASES = {
    qualification: "qualifications",
    project: "projects",
    publication: "publications",
    achievement: "achievements",
    certification: "certifications"
  };

  function applySectionAliases(profile) {
    Object.keys(SECTION_ALIASES).forEach(function (type) {
      profile[SECTION_ALIASES[type]] = Array.isArray(profile[type]) ? profile[type] : [];
    });
    return profile;
  }

  function seedRoster() {
    return [SAMPLE_OWNER].concat(SAMPLE_FACULTY).map(function (profile) {
      return normalizeProfile(profile);
    });
  }

  function normalizeRoster(list) {
    const seen = {};
    const roster = [];
    (Array.isArray(list) ? list : []).forEach(function (raw) {
      if (!raw || typeof raw !== "object") return;
      const profile = normalizeProfile(raw);
      if (!profile.id) return;
      let id = profile.id;
      let suffix = 2;
      while (seen[id]) {
        id = profile.id + "-" + suffix;
        suffix += 1;
      }
      profile.id = id;
      seen[id] = true;
      roster.push(profile);
    });
    return roster;
  }

  function loadState() {
    let stored = null;
    try {
      stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || "null");
    } catch {
      stored = null;
    }
    const role = stored && ROLES[stored.role] ? stored.role : "faculty";
    const storedRoster = stored && Array.isArray(stored.roster) && stored.roster.length ? stored.roster : seedRoster();
    const roster = normalizeRoster(storedRoster);
    const list = roster.length ? roster : seedRoster();
    const activeFacultyId = stored && list.some(function (profile) { return profile.id === stored.activeFacultyId; })
      ? stored.activeFacultyId
      : list[0].id;
    return {
      role: role,
      roster: list,
      activeFacultyId: activeFacultyId,
      session: !!(stored && stored.session)
    };
  }

  function persistState() {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
        role: state.role,
        roster: state.roster,
        activeFacultyId: state.activeFacultyId,
        session: state.session
      }));
    } catch (error) {
      console.warn("Faculty Portal: unable to persist demo data.", error);
    }
  }

  function allFaculty() {
    return state.roster;
  }

  function findFaculty(id) {
    return allFaculty().filter(function (profile) { return profile.id === id; })[0] || null;
  }

  function findFacultyByEmail(value) {
    const email = String(value || "").trim().toLowerCase();
    if (!email) return null;
    return allFaculty().filter(function (profile) {
      return (profile.email || "").toLowerCase() === email;
    })[0] || null;
  }

  function currentFaculty() {
    return findFaculty(state.activeFacultyId) || state.roster[0] || null;
  }

  function isAdmin() {
    return state.role === "admin";
  }

  function isFaculty() {
    return state.role === "faculty";
  }

  function isViewer() {
    return state.role === "viewer";
  }

  function canEditProfile(profile) {
    if (!profile) return false;
    if (isAdmin()) return true;
    return isFaculty() && profile.id === state.activeFacultyId;
  }

  function profileStarted(profile) {
    if (!profile) return false;
    return personalComplete(profile) || ["qualification", "experience", "projects", "publications", "achievements", "certifications"].some(function (key) {
      return countItems(profile[key]) > 0;
    });
  }

  function todayLabel() {
    return new Intl.DateTimeFormat("en-IN", { weekday: "long", day: "numeric", month: "long", year: "numeric" }).format(new Date());
  }

  function specializationTags(profile, limit) {
    return String(profile.specialization || "")
      .split(",")
      .map(function (item) { return item.trim(); })
      .filter(Boolean)
      .slice(0, limit || 3);
  }

  function contactRow(profile, compact) {
    const parts = [];
    if (profile.email) {
      parts.push(`<a href="mailto:${escapeHTML(profile.email)}"><i class="bi bi-envelope" aria-hidden="true"></i> ${escapeHTML(profile.email)}</a>`);
    }
    if (profile.contact) {
      parts.push(`<span><i class="bi bi-telephone" aria-hidden="true"></i> ${escapeHTML(profile.contact)}</span>`);
    }
    if (!compact && profile.facultyId) {
      parts.push(`<span><i class="bi bi-person-vcard" aria-hidden="true"></i> ${escapeHTML(profile.facultyId)}</span>`);
    }
    return parts.length ? `<div class="contact-row">${parts.join("")}</div>` : "";
  }

  function directoryStats() {
    const profiles = allFaculty();
    const complete = profiles.filter(function (profile) {
      return computeCompletion(profile).percent >= 100;
    }).length;
    const total = profiles.length;
    return {
      total: total,
      complete: complete,
      incomplete: total - complete,
      rate: total ? Math.round((complete / total) * 100) : 0
    };
  }

  function departmentBreakdown() {
    const counts = {};
    allFaculty().forEach(function (profile) {
      const department = profile.department || "Not assigned";
      counts[department] = (counts[department] || 0) + 1;
    });
    return Object.keys(counts).map(function (department) {
      return { department: department, count: counts[department] };
    }).sort(function (a, b) {
      return b.count - a.count || a.department.localeCompare(b.department);
    });
  }

  function renderAdminOverview() {
    const stats = directoryStats();
    const total = $("#admin-total");
    const complete = $("#admin-complete");
    const incomplete = $("#admin-incomplete");
    const rate = $("#admin-rate");
    if (total) total.textContent = String(stats.total);
    if (complete) complete.textContent = String(stats.complete);
    if (incomplete) incomplete.textContent = String(stats.incomplete);
    if (rate) rate.textContent = stats.rate + "%";

    const list = $("#admin-departments");
    if (!list) return;
    const departments = departmentBreakdown();
    const max = departments.reduce(function (highest, item) { return Math.max(highest, item.count); }, 1);
    list.innerHTML = departments.map(function (item) {
      return `
        <div class="department-row">
          <div class="department-row__top">
            <span>${escapeHTML(item.department)}</span>
            <strong>${item.count}</strong>
          </div>
          <div class="progress-track"><span style="width:${Math.round((item.count / max) * 100)}%"></span></div>
        </div>
      `;
    }).join("");
  }

  function renderStrength(completion, options) {
    const opts = options || {};
    $("#strength-value").textContent = (opts.value != null ? opts.value : completion.percent) + "%";
    $("#strength-sections").textContent = opts.sections || (completion.filled + " of " + completion.total + " sections");
    $("#strength-status").textContent = opts.status || completionBadge(completion.percent).label;
    $("#strength-bar-fill").style.width = (opts.value != null ? opts.value : completion.percent) + "%";

    const ring = $("#strength-ring-value");
    if (ring) {
      const percent = opts.value != null ? opts.value : completion.percent;
      const circumference = 2 * Math.PI * 52;
      ring.style.strokeDasharray = String(circumference);
      ring.style.strokeDashoffset = String(circumference * (1 - percent / 100));
    }

    $("#strength-caption").textContent = opts.caption || "";
  }

  function renderDashboard() {
    const profile = currentFaculty();
    const role = state.role;
    const isAdminView = role === "admin";
    const isViewerView = role === "viewer";
    const completion = computeCompletion(profile);

    $("#today-label").textContent = todayLabel();

    const strengthCard = $(".strength-card");
    const profileCard = $("#dashboard-profile-card");
    const adminOverview = $("#admin-overview");
    if (strengthCard) strengthCard.hidden = isViewerView;
    if (profileCard) profileCard.hidden = isAdminView || isViewerView;
    if (adminOverview) adminOverview.hidden = !isAdminView;

    if (isAdminView) {
      const stats = directoryStats();
      $("#welcome-name").textContent = "Administrator";
      $("#welcome-sub").textContent = "Manage faculty profiles, monitor completion and keep the university directory current.";
      $("#hero-mode-note").innerHTML = '<i class="bi bi-shield-lock" aria-hidden="true"></i> Admin mode';
      $("#hero-actions").innerHTML = [
        '<button class="btn btn-white" type="button" data-action="new-profile"><i class="bi bi-person-plus" aria-hidden="true"></i> Add faculty</button>',
        '<button class="btn btn-glass" type="button" data-action="scroll-directory"><i class="bi bi-sliders" aria-hidden="true"></i> Manage directory</button>'
      ].join("");
      renderAdminOverview();
      renderStrength(completion, {
        value: stats.rate,
        sections: stats.complete + " of " + stats.total + " profiles complete",
        status: stats.incomplete ? "Needs review" : "All complete",
        caption: stats.incomplete
          ? stats.incomplete + " faculty profile" + (stats.incomplete === 1 ? "" : "s") + " still need attention."
          : "Every faculty profile is complete and directory ready."
      });
      return;
    }

    const welcomeName = profile.name || "Faculty Member";
    const nameParts = welcomeName.replace(/^(Dr|Prof|Mr|Ms|Mrs)\.?\s+/i, "").split(/\s+/).filter(Boolean);
    const welcomeFirst = nameParts.filter(function (part) { return part.length > 1 && !/^[A-Z]\.$/.test(part); })[0] || nameParts[0] || welcomeName;

    if (isViewerView) {
      $("#welcome-name").textContent = "Guest";
      $("#welcome-sub").textContent = "Browse the UCEN faculty directory and explore academic profiles across departments.";
      $("#hero-mode-note").innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i> Viewer mode';
      $("#hero-actions").innerHTML = '<button class="btn btn-white" type="button" data-action="scroll-directory"><i class="bi bi-people" aria-hidden="true"></i> Browse directory</button>';
      return;
    }

    $("#welcome-name").textContent = welcomeFirst;
    $("#welcome-sub").textContent = profileStarted(profile)
      ? "Keep your academic profile current and help colleagues discover your work."
      : "Create your faculty profile to share your teaching, research and achievements.";
    $("#hero-mode-note").innerHTML = '<i class="bi bi-person-badge" aria-hidden="true"></i> Faculty mode';

    const actions = [];
    if (profileStarted(profile)) {
      actions.push('<button class="btn btn-white" type="button" data-action="edit-profile"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit my profile</button>');
    } else {
      actions.push('<button class="btn btn-white" type="button" data-action="create-own"><i class="bi bi-person-plus" aria-hidden="true"></i> Create profile</button>');
    }
    actions.push('<button class="btn btn-glass" type="button" data-action="view-owner"><i class="bi bi-person-circle" aria-hidden="true"></i> View my profile</button>');
    actions.push('<button class="btn btn-glass" type="button" data-action="scroll-directory"><i class="bi bi-people" aria-hidden="true"></i> Browse directory</button>');
    $("#hero-actions").innerHTML = actions.join("");

    const missing = completion.sections.filter(function (section) { return !section.complete; });
    renderStrength(completion, {
      caption: completion.percent >= 100
        ? "Your profile is complete. Colleagues can now discover every part of your academic work."
        : missing.length === 1
          ? "Add " + missing[0].label.toLowerCase() + " to finish your profile."
          : "Add " + missing[0].label.toLowerCase() + " and " + missing[1].label.toLowerCase() + " to strengthen your profile."
    });

    $("#dashboard-profile").innerHTML = `
      ${avatarHTML(profile, "avatar--lg")}
      <div class="profile-strip__copy">
        <h2>${escapeHTML(profile.name || "Your profile")}</h2>
        <p class="profile-strip__designation">${escapeHTML(profile.designation || "Designation not added")}</p>
        <p class="profile-strip__department">${escapeHTML(profile.department || "Department not added")}</p>
        ${contactRow(profile)}
      </div>
    `;

    const tags = specializationTags(profile, 3).map(function (tag) {
      return `<span class="tag"><i class="bi bi-stars" aria-hidden="true"></i> ${escapeHTML(tag)}</span>`;
    });
    tags.unshift(`<span class="tag"><i class="bi bi-building" aria-hidden="true"></i> ${escapeHTML(departmentShort(profile.department))}</span>`);
    if (profile.facultyId) {
      tags.push(`<span class="tag"><i class="bi bi-hash" aria-hidden="true"></i> ${escapeHTML(profile.facultyId)}</span>`);
    }
    $("#dashboard-tags").innerHTML = tags.join("");

    const stats = [
      { value: countItems(profile.qualification), label: "Degrees" },
      { value: countItems(profile.experience), label: "Roles" },
      { value: countItems(profile.projects), label: "Projects" },
      { value: countItems(profile.publications), label: "Papers" }
    ];
    $("#dashboard-stats").innerHTML = stats.map(function (stat) {
      return `<div class="mini-stat"><strong>${stat.value}</strong><span>${stat.label}</span></div>`;
    }).join("");
  }

  function populateFilters() {
    const departmentSelect = $("#directory-department");
    const designationSelect = $("#directory-designation");
    if (departmentSelect && departmentSelect.options.length <= 1) {
      DEPARTMENTS.forEach(function (department) {
        const option = document.createElement("option");
        option.value = department;
        option.textContent = department;
        departmentSelect.appendChild(option);
      });
    }
    if (designationSelect && designationSelect.options.length <= 1) {
      DESIGNATIONS.forEach(function (designation) {
        const option = document.createElement("option");
        option.value = designation;
        option.textContent = designation;
        designationSelect.appendChild(option);
      });
    }

    const formDepartment = $("#p-department");
    const formDesignation = $("#p-designation");
    if (formDepartment && formDepartment.options.length <= 1) {
      DEPARTMENTS.forEach(function (department) {
        const option = document.createElement("option");
        option.value = department;
        option.textContent = department;
        formDepartment.appendChild(option);
      });
    }
    if (formDesignation && formDesignation.options.length <= 1) {
      DESIGNATIONS.forEach(function (designation) {
        const option = document.createElement("option");
        option.value = designation;
        option.textContent = designation;
        formDesignation.appendChild(option);
      });
    }
  }

  function filterFaculty() {
    const query = ($("#directory-search") && $("#directory-search").value || "").trim().toLowerCase();
    const department = ($("#directory-department") && $("#directory-department").value) || "";
    const designation = ($("#directory-designation") && $("#directory-designation").value) || "";

    return allFaculty().filter(function (profile) {
      if (department && profile.department !== department) return false;
      if (designation && profile.designation !== designation) return false;
      if (!query) return true;
      const haystack = [
        profile.name,
        profile.department,
        profile.designation,
        profile.specialization,
        profile.facultyId,
        profile.email
      ].join(" ").toLowerCase();
      return haystack.indexOf(query) !== -1;
    });
  }

  function facultyCard(profile) {
    const tags = specializationTags(profile, 2).map(function (tag) {
      return `<span class="tag">${escapeHTML(tag)}</span>`;
    }).join("");
    const secondaryCount = countItems(profile.publications) + countItems(profile.projects);
    const footerText = countItems(profile.publications) + " publication" + (countItems(profile.publications) === 1 ? "" : "s")
      + (secondaryCount ? " · " + countItems(profile.projects) + " project" + (countItems(profile.projects) === 1 ? "" : "s") : "");
    const editButton = isAdmin()
      ? `<button class="btn btn-ghost btn-sm" type="button" data-edit-profile="${escapeHTML(profile.id)}"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</button>`
      : "";

    return `
      <article class="faculty-card">
        <div class="faculty-card__top">
          ${avatarHTML(profile)}
          <span class="department-chip">${escapeHTML(departmentShort(profile.department))}</span>
        </div>
        <h3>${escapeHTML(profile.name)}</h3>
        <p class="faculty-card__designation">${escapeHTML(profile.designation || "Faculty")}</p>
        <p class="faculty-card__dept">${escapeHTML(profile.department || "")}</p>
        <div class="faculty-card__tags">${tags || '<span class="tag">Faculty profile</span>'}</div>
        <div class="faculty-card__footer">
          <span>${escapeHTML(footerText)}</span>
          <div class="faculty-card__actions">
            ${editButton}
            <button class="btn btn-soft btn-sm" type="button" data-view-profile="${escapeHTML(profile.id)}">
              View <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </article>
    `;
  }

  function renderDirectory() {
    const grid = $("#directory-grid");
    const empty = $("#directory-empty");
    if (!grid) return;
    const results = filterFaculty();
    grid.innerHTML = results.map(facultyCard).join("");
    $("#directory-count").textContent = String(results.length);
    if (empty) empty.hidden = results.length !== 0;
    if (grid) grid.hidden = results.length === 0;
  }

  function sectionHead(icon, title, subtitle, count) {
    const countHTML = typeof count === "number"
      ? `<span class="profile-section__count">${count} ${count === 1 ? "entry" : "entries"}</span>`
      : "";
    return `
      <header class="profile-section__head">
        <span class="icon-badge"><i class="bi ${icon}" aria-hidden="true"></i></span>
        <div>
          <h2>${escapeHTML(title)}</h2>
          <p>${escapeHTML(subtitle)}</p>
        </div>
        ${countHTML}
      </header>
    `;
  }

  function sectionEmpty(message, icon) {
    return `
      <div class="section-empty">
        <i class="bi ${icon || "bi-inbox"}" aria-hidden="true"></i>
        <p>${escapeHTML(message)}</p>
      </div>
    `;
  }

  function profileSection(id, attributes, headHTML, bodyHTML) {
    return `
      <section class="card profile-section" id="${id}" ${attributes || ""}>
        ${headHTML}
        ${bodyHTML}
      </section>
    `;
  }

  function qualificationBody(profile) {
    if (!profile.qualification.length) return sectionEmpty("No qualifications have been added yet.", "bi-mortarboard");
    return `
      <div class="timeline">
        ${profile.qualification.map(function (item) {
          return `
            <div class="timeline__item">
              <span class="timeline__dot" aria-hidden="true"></span>
              <div class="timeline__top">
                <h3>${escapeHTML(item.degree || "Degree")}</h3>
                ${item.year ? `<span class="timeline__when">${escapeHTML(item.year)}</span>` : ""}
              </div>
              ${item.institution ? `<p class="timeline__sub">${escapeHTML(item.institution)}</p>` : ""}
              <p class="timeline__meta">${[item.university, item.specialization].filter(Boolean).map(escapeHTML).join(" · ") || "—"}</p>
            </div>
          `;
        }).join("")}
      </div>
    `;
  }

  function experienceBody(profile) {
    if (!profile.experience.length) return sectionEmpty("No professional experience has been added yet.", "bi-briefcase");
    return `
      <div class="timeline">
        ${profile.experience.map(function (item) {
          return `
            <div class="timeline__item">
              <span class="timeline__dot" aria-hidden="true"></span>
              <div class="timeline__top">
                <h3>${escapeHTML(item.designation || item.institution || "Role")}</h3>
                ${dateRange(item.from, item.to) ? `<span class="timeline__when">${escapeHTML(dateRange(item.from, item.to))}</span>` : ""}
              </div>
              ${item.institution ? `<p class="timeline__sub">${escapeHTML(item.institution)}</p>` : ""}
              ${item.total ? `<p class="timeline__meta">Total experience: ${escapeHTML(item.total)}</p>` : ""}
              ${item.description ? `<p class="timeline__desc">${escapeHTML(item.description)}</p>` : ""}
            </div>
          `;
        }).join("")}
      </div>
    `;
  }

  function projectBody(profile) {
    if (!profile.projects.length) return sectionEmpty("No funded projects have been added yet.", "bi-cash-stack");
    return `
      <div class="project-grid">
        ${profile.projects.map(function (item) {
          const status = String(item.status || "").toLowerCase();
          const statusClass = ["ongoing", "completed", "approved", "proposed"].indexOf(status) !== -1 ? " status-pill--" + status : "";
          return `
            <article class="project-card">
              <div class="project-card__top">
                <h3>${escapeHTML(item.title || "Funded project")}</h3>
                ${item.status ? `<span class="status-pill${statusClass}">${escapeHTML(item.status)}</span>` : ""}
              </div>
              ${item.agency ? `<p class="project-agency">${escapeHTML(item.agency)}</p>` : ""}
              <div class="project-meta">
                ${item.role ? `<span><i class="bi bi-person-badge" aria-hidden="true"></i> ${escapeHTML(item.role)}</span>` : ""}
                ${item.duration ? `<span><i class="bi bi-calendar3" aria-hidden="true"></i> ${escapeHTML(item.duration)}</span>` : ""}
              </div>
              ${formatAmount(item.amount) ? `<p class="project-amount">${escapeHTML(formatAmount(item.amount))}</p>` : ""}
              ${item.description ? `<p class="project-desc">${escapeHTML(item.description)}</p>` : ""}
            </article>
          `;
        }).join("")}
      </div>
    `;
  }

  function publicationBody(profile) {
    if (!profile.publications.length) return sectionEmpty("No publications have been added yet.", "bi-journal-richtext");
    return `
      <div class="publication-list">
        ${profile.publications.map(function (item) {
          const link = externalUrl(item.link);
          return `
            <article class="publication-item">
              <span class="pub-year">${escapeHTML(item.year || "—")}</span>
              <div class="pub-body">
                <h3>${escapeHTML(item.title || "Publication")}</h3>
                ${item.venue ? `<p class="pub-venue">${escapeHTML(item.venue)}${item.type ? " &middot; " + escapeHTML(item.type) : ""}</p>` : ""}
                ${link ? `<a class="pub-link" href="${escapeHTML(link)}" target="_blank" rel="noopener noreferrer"><i class="bi bi-link-45deg" aria-hidden="true"></i> ${escapeHTML(item.link)}</a>` : ""}
              </div>
            </article>
          `;
        }).join("")}
      </div>
    `;
  }

  function achievementBody(profile) {
    if (!profile.achievements.length) return sectionEmpty("No achievements have been added yet.", "bi-trophy");
    return `
      <div class="card-grid">
        ${profile.achievements.map(function (item) {
          return `
            <article class="achievement-card">
              <span class="achievement-card__icon"><i class="bi bi-trophy" aria-hidden="true"></i></span>
              <h3>${escapeHTML(item.title || "Achievement")}</h3>
              ${item.organization ? `<p class="achievement-card__org">${escapeHTML(item.organization)}</p>` : ""}
              ${item.year ? `<span class="achievement-card__year">${escapeHTML(item.year)}</span>` : ""}
              ${item.description ? `<p>${escapeHTML(item.description)}</p>` : ""}
            </article>
          `;
        }).join("")}
      </div>
    `;
  }

  function certificationBody(profile) {
    if (!profile.certifications.length) return sectionEmpty("No certifications have been added yet.", "bi-patch-check");
    return `
      <div class="card-grid">
        ${profile.certifications.map(function (item) {
          return `
            <article class="achievement-card">
              <span class="achievement-card__icon achievement-card__icon--cert"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
              <h3>${escapeHTML(item.title || "Certification")}</h3>
              ${item.organization ? `<p class="achievement-card__org">${escapeHTML(item.organization)}</p>` : ""}
              ${item.year ? `<span class="achievement-card__year">${escapeHTML(item.year)}</span>` : ""}
              ${item.description ? `<p>${escapeHTML(item.description)}</p>` : ""}
            </article>
          `;
        }).join("")}
      </div>
    `;
  }

  function buildProfile(profile, options) {
    const opts = options || {};
    const prefix = opts.prefix || "profile-";
    const canEdit = !!opts.canEdit;
    const tags = specializationTags(profile, 5);
    const tagsHTML = tags.length
      ? tags.map(function (tag) { return `<span class="tag"><i class="bi bi-stars" aria-hidden="true"></i> ${escapeHTML(tag)}</span>`; }).join("")
      : '<span class="tag"><i class="bi bi-stars" aria-hidden="true"></i> Specialisation not added</span>';

    const contacts = [];
    if (profile.email) contacts.push(`<a href="mailto:${escapeHTML(profile.email)}"><i class="bi bi-envelope" aria-hidden="true"></i> ${escapeHTML(profile.email)}</a>`);
    if (profile.contact) contacts.push(`<span><i class="bi bi-telephone" aria-hidden="true"></i> ${escapeHTML(profile.contact)}</span>`);
    if (profile.facultyId) contacts.push(`<span><i class="bi bi-person-vcard" aria-hidden="true"></i> ${escapeHTML(profile.facultyId)}</span>`);

    const actionsHTML = canEdit
      ? `<div class="profile-hero__actions"><button class="btn btn-primary" type="button" data-action="edit-profile"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit profile</button></div>`
      : "";

    const personalBody = `
      <div class="info-grid">
        <div class="info-item"><span class="info-item__label">Faculty ID</span><p class="info-item__value">${escapeHTML(profile.facultyId || "Not added")}</p></div>
        <div class="info-item"><span class="info-item__label">Department</span><p class="info-item__value">${escapeHTML(profile.department || "Not added")}</p></div>
        <div class="info-item"><span class="info-item__label">Designation</span><p class="info-item__value">${escapeHTML(profile.designation || "Not added")}</p></div>
        <div class="info-item"><span class="info-item__label">Email</span><p class="info-item__value">${profile.email ? `<a href="mailto:${escapeHTML(profile.email)}">${escapeHTML(profile.email)}</a>` : "Not added"}</p></div>
        <div class="info-item"><span class="info-item__label">Contact</span><p class="info-item__value">${escapeHTML(profile.contact || "Not added")}</p></div>
        <div class="info-item"><span class="info-item__label">Specialisation</span><p class="info-item__value">${escapeHTML(profile.specialization || "Not added")}</p></div>
        <div class="info-item info-item--full">
          <span class="info-item__label">Biography</span>
          <p class="info-item__value info-bio">${profile.bio ? escapeHTML(profile.bio) : "No biography has been added yet."}</p>
        </div>
      </div>
    `;

    return `
      <div id="${prefix}overview" data-sidebar-section="overview">
      <div class="profile-hero">
        <div class="profile-hero__banner"></div>
        <div class="profile-hero__body">
          <div class="profile-hero__avatar">${avatarHTML(profile)}</div>
          <div class="profile-hero__main">
            <div class="badge-row">
              <span class="badge">${escapeHTML(departmentShort(profile.department))}</span>
              <span class="badge badge--green"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Faculty</span>
            </div>
            <h1>${escapeHTML(profile.name || "Faculty Member")}</h1>
            <p class="profile-hero__sub">${escapeHTML(profile.designation || "Designation not added")} <span>&middot; ${escapeHTML(profile.department || "Department not added")}</span></p>
            ${contacts.length ? `<div class="profile-hero__contacts">${contacts.join("")}</div>` : ""}
            <div class="profile-hero__tags">${tagsHTML}</div>
          </div>
          ${actionsHTML}
        </div>
      </div>
      </div>

      ${profileSection(prefix + "personal", 'data-sidebar-section="personal"', sectionHead("bi-person-vcard", "Personal Information", "Academic identity and contact details"), personalBody)}
      ${profileSection(prefix + "qualification", 'data-sidebar-section="qualification"', sectionHead("bi-mortarboard", "Qualification", "Degrees, fellowships and academic credentials", profile.qualification.length), qualificationBody(profile))}
      ${profileSection(prefix + "experience", 'data-sidebar-section="experience"', sectionHead("bi-briefcase", "Professional Experience", "Teaching, research and industry roles", profile.experience.length), experienceBody(profile))}
      ${profileSection(prefix + "projects", 'data-sidebar-section="projects"', sectionHead("bi-cash-stack", "Funded Projects", "Sponsored research and consultancy projects", profile.projects.length), projectBody(profile))}
      ${profileSection(prefix + "publications", 'data-sidebar-section="publications"', sectionHead("bi-journal-richtext", "Publications", "Journal articles, conference papers and chapters", profile.publications.length), publicationBody(profile))}
      ${profileSection(prefix + "achievements", 'data-sidebar-section="achievements"', sectionHead("bi-trophy", "Achievements", "Awards, honours and recognition", profile.achievements.length), achievementBody(profile))}
      ${profileSection(prefix + "certifications", 'data-sidebar-section="certifications"', sectionHead("bi-patch-check", "Certifications", "Professional and technical credentials", profile.certifications.length), certificationBody(profile))}
    `;
  }

  function renderProfileView(profile) {
    const content = $("#profile-content");
    if (!content) return;
    const isOwn = profile.id === state.activeFacultyId;
    const canEdit = canEditProfile(profile);
    content.innerHTML = buildProfile(profile, { canEdit: canEdit, prefix: "profile-" });

    const note = $("#profile-mode-note");
    if (note) {
      if (isAdmin()) {
        note.innerHTML = '<i class="bi bi-shield-lock" aria-hidden="true"></i> Admin editing access';
      } else if (canEdit) {
        note.innerHTML = '<i class="bi bi-person-badge" aria-hidden="true"></i> Your profile &middot; editable';
      } else {
        note.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i> Read-only view';
      }
    }

    const backLabel = $("#profile-back-label");
    if (backLabel) backLabel.textContent = isFaculty() && isOwn ? "Back to dashboard" : "Back to directory";

    state.viewed = profile;
  }

  function renderPreview(profile) {
    const body = $("#preview-body");
    if (!body) return;
    body.innerHTML = buildProfile(profile, { canEdit: false, prefix: "preview-" });
  }

  function entryFieldHTML(type, field, entry) {
    const value = entry && entry[field.key] != null ? String(entry[field.key]) : "";
    const id = type + "-" + field.key + "-" + entryUid;
    const label = `<label for="${id}">${escapeHTML(field.label)}${field.required ? ' <span class="req">*</span>' : ""}</label>`;
    const attrs = [
      `id="${id}"`,
      `data-field="${escapeHTML(field.key)}"`,
      `data-label="${escapeHTML(field.label)}"`,
      field.required ? 'data-required="true"' : "",
      field.min != null ? `min="${field.min}"` : "",
      field.max != null ? `max="${field.max}"` : ""
    ].filter(Boolean).join(" ");

    let control = "";
    if (field.type === "textarea") {
      control = `<textarea ${attrs} rows="3" placeholder="${escapeHTML(field.placeholder || "")}">${escapeHTML(value)}</textarea>`;
    } else if (field.type === "select") {
      const options = (field.options || []).map(function (option) {
        const selected = value === option ? " selected" : "";
        return `<option value="${escapeHTML(option)}"${selected}>${escapeHTML(option)}</option>`;
      }).join("");
      control = `<select ${attrs}><option value="">Select ${escapeHTML(field.label.toLowerCase())}</option>${options}</select>`;
    } else {
      control = `<input type="${field.type || "text"}" ${attrs} value="${escapeHTML(value)}" placeholder="${escapeHTML(field.placeholder || "")}">`;
    }

    return `<div class="field${field.full ? " field--full" : ""}">${label}${control}</div>`;
  }

  function entryCardHTML(type, entry) {
    entryUid += 1;
    const config = ENTRY_CONFIG[type];
    const fields = config.fields.map(function (field) {
      return entryFieldHTML(type, field, entry);
    }).join("");
    return `
      <div class="entry-card" data-entry-type="${escapeHTML(type)}" data-entry-uid="${entryUid}" data-entry-index="0">
        <div class="entry-card__head">
          <span class="entry-index">${escapeHTML(config.label)}</span>
          <button class="entry-remove" type="button" data-remove-entry aria-label="Remove ${escapeHTML(config.label.toLowerCase())}">
            <i class="bi bi-trash3" aria-hidden="true"></i>
          </button>
        </div>
        <div class="form-grid">${fields}</div>
      </div>
    `;
  }

  function renumberEntryLists() {
    Object.keys(ENTRY_CONFIG).forEach(function (type) {
      const container = $("#" + ENTRY_CONFIG[type].container);
      if (!container) return;
      $$(".entry-card", container).forEach(function (card, index) {
        card.dataset.entryIndex = String(index);
        const label = $(".entry-index", card);
        if (label) label.textContent = ENTRY_CONFIG[type].label + " " + (index + 1);
      });
    });
  }

  function renderEntryList(type, items) {
    const container = $("#" + ENTRY_CONFIG[type].container);
    if (!container) return;
    const list = Array.isArray(items) && items.length ? items : [{}];
    container.innerHTML = list.map(function (entry) {
      return entryCardHTML(type, entry);
    }).join("");
    renumberEntryLists();
  }

  function addEntry(type, entry) {
    const container = $("#" + ENTRY_CONFIG[type].container);
    if (!container) return;
    const wrapper = document.createElement("div");
    wrapper.innerHTML = entryCardHTML(type, entry || {});
    const card = wrapper.firstElementChild;
    container.appendChild(card);
    renumberEntryLists();
    updateFormCompletion();
    const firstField = $("[data-field]", card);
    if (firstField) firstField.focus({ preventScroll: true });
    card.scrollIntoView({ behavior: REDUCED_MOTION ? "auto" : "smooth", block: "center" });
  }

  function removeEntry(button) {
    const card = button.closest(".entry-card");
    if (!card) return;
    const container = card.parentElement;
    const type = card.dataset.entryType;
    const hasContent = $$("[data-field]", card).some(function (field) {
      return String(field.value || "").trim() !== "";
    });

    if (hasContent && !window.confirm("Remove this entry? Any information typed into it will be lost.")) {
      return;
    }

    card.remove();
    if (container && !$(".entry-card", container)) {
      container.innerHTML = entryCardHTML(type, {});
    }
    renumberEntryLists();
    updateFormCompletion();
  }

  function renderPhotoPreview() {
    const preview = $("#photo-preview");
    if (!preview) return;
    const profile = currentFaculty();
    const name = ($("#p-name") && $("#p-name").value) || (profile && profile.name) || "Faculty Member";
    if (state.draftPhoto) {
      preview.innerHTML = `<img src="${escapeHTML(state.draftPhoto)}" alt="Profile photo preview"><button class="photo-preview__remove" type="button" data-action="remove-photo" aria-label="Remove photo"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`;
    } else {
      const gradient = gradientFor(name);
      preview.innerHTML = `<div class="avatar avatar--initials" style="--av-a:${gradient[0]};--av-b:${gradient[1]}"><span aria-hidden="true">${escapeHTML(initialsOf(name))}</span></div>`;
    }
  }

  function fillPersonal(profile) {
    const map = {
      "p-name": profile.name,
      "p-faculty-id": profile.facultyId,
      "p-department": profile.department,
      "p-designation": profile.designation,
      "p-email": profile.email,
      "p-contact": profile.contact,
      "p-specialization": profile.specialization,
      "p-bio": profile.bio
    };
    Object.keys(map).forEach(function (id) {
      const field = $("#" + id);
      if (field) field.value = map[id] || "";
    });
  }

  function fillForm(profile, options) {
    const opts = options || {};
    const source = normalizeProfile(profile || SAMPLE_OWNER);
    if (opts.blankName) source.name = "";
    state.formId = profile && profile.id ? profile.id : "";
    state.draftPhoto = source.photo || "";
    fillPersonal(source);
    Object.keys(ENTRY_CONFIG).forEach(function (type) {
      renderEntryList(type, source[type]);
    });
    renderPhotoPreview();
    updateFormCompletion();
    clearFormErrors();
  }

  function handlePhotoFile(file) {
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
      showToast("Please choose a JPG, PNG or WebP image.", "error");
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showToast("That image is larger than 5 MB. Please choose a smaller file.", "error");
      return;
    }

    const reader = new FileReader();
    reader.onload = function () {
      const image = new Image();
      image.onload = function () {
        const size = 480;
        const canvas = document.createElement("canvas");
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext("2d");
        const side = Math.min(image.width, image.height);
        const sx = (image.width - side) / 2;
        const sy = (image.height - side) / 2;
        context.drawImage(image, sx, sy, side, side, 0, 0, size, size);
        state.draftPhoto = canvas.toDataURL("image/jpeg", 0.86);
        renderPhotoPreview();
        showToast("Photo ready. Remember to save your profile.", "success");
      };
      image.onerror = function () {
        showToast("We could not read that image. Please try another file.", "error");
      };
      image.src = String(reader.result);
    };
    reader.onerror = function () {
      showToast("We could not read that file. Please try again.", "error");
    };
    reader.readAsDataURL(file);
  }

  function fieldValue(id) {
    const field = $("#" + id);
    return field ? String(field.value || "").trim() : "";
  }

  function collectForm() {
    const profile = {
      id: state.formId || "fac-owner",
      name: fieldValue("p-name"),
      facultyId: fieldValue("p-faculty-id"),
      department: fieldValue("p-department"),
      designation: fieldValue("p-designation"),
      email: fieldValue("p-email"),
      contact: fieldValue("p-contact"),
      specialization: fieldValue("p-specialization"),
      bio: fieldValue("p-bio"),
      photo: state.draftPhoto || ""
    };

    Object.keys(ENTRY_CONFIG).forEach(function (type) {
      const container = $("#" + ENTRY_CONFIG[type].container);
      const entries = [];
      if (container) {
        $$(".entry-card", container).forEach(function (card) {
          const entry = {};
          let hasValue = false;
          $$("[data-field]", card).forEach(function (input) {
            const value = String(input.value || "").trim();
            entry[input.dataset.field] = value;
            if (value) hasValue = true;
          });
          if (hasValue) entries.push(entry);
        });
      }
      profile[type] = entries;
    });

    return applySectionAliases(profile);
  }

  function clearFormErrors() {
    $$("#profile-form .is-invalid").forEach(function (field) {
      field.classList.remove("is-invalid");
    });
    $$("#profile-form .field-error").forEach(function (error) {
      error.remove();
    });
    $$("#profile-form [aria-invalid='true']").forEach(function (input) {
      input.removeAttribute("aria-invalid");
    });
  }

  function setFieldError(input, message) {
    const field = input.closest(".field");
    if (!field) return;
    field.classList.add("is-invalid");
    input.setAttribute("aria-invalid", "true");
    let error = $(".field-error", field);
    if (!error) {
      error = document.createElement("span");
      error.className = "field-error";
      field.appendChild(error);
    }
    error.innerHTML = '<i class="bi bi-exclamation-circle" aria-hidden="true"></i> ' + escapeHTML(message);
  }

  function validateForm() {
    clearFormErrors();
    const invalid = [];

    const checks = [
      { id: "p-name", message: "Please enter your full name." },
      { id: "p-faculty-id", message: "Please enter your faculty ID." },
      { id: "p-department", message: "Please select your department." },
      { id: "p-designation", message: "Please select your designation." },
      { id: "p-email", message: "Please enter your email address." },
      { id: "p-contact", message: "Please enter your contact number." },
      { id: "p-specialization", message: "Please add at least one specialisation." }
    ];

    checks.forEach(function (check) {
      const input = $("#" + check.id);
      if (!input) return;
      const value = String(input.value || "").trim();
      if (!value) {
        setFieldError(input, check.message);
        invalid.push(input);
        return;
      }
      if (check.id === "p-email" && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
        setFieldError(input, "Enter a valid email address, for example name@ucen.ac.in.");
        invalid.push(input);
      }
      if (check.id === "p-contact") {
        const digits = value.replace(/\D/g, "");
        if (digits.length < 7 || digits.length > 15) {
          setFieldError(input, "Enter a valid contact number with 7 to 15 digits.");
          invalid.push(input);
        }
      }
    });

    Object.keys(ENTRY_CONFIG).forEach(function (type) {
      const container = $("#" + ENTRY_CONFIG[type].container);
      if (!container) return;
      $$(".entry-card", container).forEach(function (card) {
        const inputs = $$("[data-field]", card);
        const hasValue = inputs.some(function (input) { return String(input.value || "").trim() !== ""; });
        if (!hasValue) return;
        inputs.forEach(function (input) {
          if (input.dataset.required !== "true") return;
          if (String(input.value || "").trim() !== "") return;
          setFieldError(input, "Please complete " + (input.dataset.label || "this field").toLowerCase() + ".");
          invalid.push(input);
        });
      });
    });

    if (invalid.length) {
      const first = invalid[0];
      const card = first.closest(".entry-card");
      if (card) {
        const targetSection = card.closest(".form-section");
        if (targetSection) setActiveSidebar(targetSection.dataset.sidebarSection || targetSection.id.replace("form-", ""));
      }
      first.scrollIntoView({ behavior: REDUCED_MOTION ? "auto" : "smooth", block: "center" });
      first.focus({ preventScroll: true });
      showToast("Please complete the highlighted fields before previewing.", "error");
      return false;
    }

    return true;
  }

  function updateFormCompletion() {
    const completion = computeCompletion(collectForm());
    const label = $("#form-completion");
    const fill = $("#form-progress-fill");
    const title = $("#form-title");
    if (label) label.textContent = completion.percent + "%";
    if (fill) fill.style.width = completion.percent + "%";
    if (title) {
      if (state.formIsNew) {
        title.textContent = "Add a new faculty profile";
      } else if (isAdmin()) {
        title.textContent = "Edit faculty profile";
      } else {
        title.textContent = completion.percent > 0 ? "Edit your faculty profile" : "Create your faculty profile";
      }
    }
  }

  function showToast(message, tone) {
    const stack = $("#toast-stack");
    if (!stack) return;
    const toast = document.createElement("div");
    toast.className = "toast toast--" + (tone === "error" ? "error" : "success");
    toast.setAttribute("role", "status");
    toast.innerHTML = `<i class="bi ${tone === "error" ? "bi-exclamation-triangle" : "bi-check-circle"}" aria-hidden="true"></i><span>${escapeHTML(message)}</span>`;
    stack.appendChild(toast);
    window.setTimeout(function () {
      toast.classList.add("is-leaving");
      window.setTimeout(function () { toast.remove(); }, 320);
    }, tone === "error" ? 4600 : 3400);
  }

  function openModal() {
    const modal = $("#preview-modal");
    if (!modal) return;
    lastFocused = document.activeElement;
    modal.hidden = false;
    document.body.classList.add("modal-open");
    const closeButton = $("[data-close-modal]", modal);
    if (closeButton) closeButton.focus({ preventScroll: true });
  }

  function closeModal() {
    const modal = $("#preview-modal");
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    document.body.classList.remove("modal-open");
    if (lastFocused && typeof lastFocused.focus === "function") {
      lastFocused.focus({ preventScroll: true });
    }
  }

  function updateFormChrome(source) {
    const title = $("#form-title");
    const subtitle = $("#form-subtitle");
    const modeNote = $("#form-mode-note");
    const creatingOwn = !isAdmin() && !profileStarted(source);
    const creating = isAdmin() ? state.formIsNew : creatingOwn;
    let heading = "Create your faculty profile";
    let copy = "Add your academic details, then preview the profile before saving.";

    if (isAdmin()) {
      heading = state.formIsNew ? "Add a new faculty profile" : "Edit faculty profile";
      copy = state.formIsNew
        ? "Create a complete faculty record, then preview it before adding it to the directory."
        : "Update this faculty member's record, then preview it before saving.";
    } else if (!creatingOwn) {
      heading = "Edit your faculty profile";
      copy = "Update any section below, then preview your profile before saving.";
    }

    if (title) title.textContent = heading;
    if (subtitle) subtitle.textContent = copy;
    if (modeNote) {
      modeNote.innerHTML = isAdmin()
        ? '<i class="bi bi-shield-lock" aria-hidden="true"></i> Admin editing mode'
        : creatingOwn
          ? '<i class="bi bi-person-plus" aria-hidden="true"></i> Creating your profile'
          : '<i class="bi bi-pencil-square" aria-hidden="true"></i> Editing your profile';
    }
    const saveButton = $("#btn-save-profile");
    if (saveButton) {
      saveButton.innerHTML = creating
        ? '<i class="bi bi-person-plus" aria-hidden="true"></i> ' + (isAdmin() ? "Add faculty" : "Create profile")
        : '<i class="bi bi-cloud-check" aria-hidden="true"></i> Save changes';
    }
    const directSave = $("#btn-save-form");
    if (directSave) {
      directSave.innerHTML = creating
        ? '<i class="bi bi-person-plus" aria-hidden="true"></i> ' + (isAdmin() ? "Add faculty" : "Create profile")
        : '<i class="bi bi-cloud-check" aria-hidden="true"></i> Save profile';
    }
  }

  function openForm(profile, options) {
    const opts = options || {};
    state.formIsNew = !!opts.isNew;
    const source = normalizeProfile(profile || currentFaculty() || SAMPLE_OWNER);
    if (opts.blankName) source.name = "";
    fillForm(source, opts);
    updateFormChrome(source);
    showView("form");
  }

  function saveProfile() {
    const fallback = currentFaculty();
    const profile = normalizeProfile(state.preview || collectForm(), fallback && fallback.name);
    profile.id = state.formId || "fac-" + Date.now().toString(36);
    const existing = state.roster.findIndex(function (item) { return item.id === profile.id; });
    if (existing >= 0) {
      state.roster[existing] = profile;
    } else {
      state.roster.push(profile);
    }
    if (isFaculty()) state.activeFacultyId = profile.id;
    state.formId = profile.id;
    state.formIsNew = false;
    state.preview = null;
    persistState();
    closeModal();
    renderDashboard();
    renderDirectory();
    renderProfileView(profile);
    updateSidebarItems();
    showView("profile");
    showToast(isAdmin() ? "Faculty profile saved to the directory." : "Profile saved to this browser.", "success");
  }

  function startFreshProfile() {
    const blank = normalizeProfile({ id: "fac-" + Date.now().toString(36) });
    blank.name = "";
    if (isAdmin()) {
      openForm(blank, { isNew: true, blankName: true });
      return;
    }
    const current = currentFaculty();
    const own = normalizeProfile({ id: current ? current.id : state.activeFacultyId });
    openForm(own, { blankName: true });
  }

  function resetDemo() {
    if (!window.confirm("Reset all demo faculty data? Saved edits and added profiles will be removed.")) return;
    try {
      window.localStorage.removeItem(STORAGE_KEY);
    } catch {}
    state.roster = seedRoster();
    state.role = "faculty";
    state.activeFacultyId = state.roster[0].id;
    state.viewed = null;
    state.session = false;
    state.formId = "";
    state.formIsNew = false;
    state.draftPhoto = "";
    updateRoleUI();
    updateSidebarItems();
    fillForm(currentFaculty());
    renderDashboard();
    renderDirectory();
    showView("dashboard");
    openRoleGate();
    showToast("Demo data restored. Choose a role to continue.", "success");
  }

  function visibleSideLinks() {
    const sidebar = $("#sidebar");
    if (!sidebar) return [];
    return $$(".side-link", sidebar).filter(function (link) {
      return !link.hidden && link.offsetParent !== null;
    });
  }

  function layoutSidebarArc() {
    const links = visibleSideLinks();
    if (!links.length) return;
    const center = (links.length - 1) / 2;
    const span = Math.max(center, 1);
    links.forEach(function (link, index) {
      const distance = Math.abs(index - center) / span;
      const offset = 6 * (1 - distance * distance);
      link.style.setProperty("--arc-x", offset.toFixed(1) + "px");
    });
  }

  function moveSidebarNeedle(target) {
    const nav = $(".sidebar__nav");
    if (!nav) return;
    const links = visibleSideLinks();
    const link = target || links.find(function (item) {
      return item.classList.contains("is-active");
    }) || links[0];
    if (!link) return;
    const navRect = nav.getBoundingClientRect();
    const rect = link.getBoundingClientRect();
    const top = rect.top - navRect.top + rect.height / 2;
    nav.style.setProperty("--needle-top", top.toFixed(1) + "px");
  }

  function handleSidebarKeydown(event) {
    const target = event.target;
    if (!target || !target.closest) return;
    if (!target.closest(".side-link, .sidebar__toggle")) return;

    const key = event.key;
    if (key === "ArrowRight" || key === "ArrowLeft") {
      const expand = key === "ArrowRight";
      if (state.sidebarExpanded !== expand) {
        event.preventDefault();
        setSidebarExpanded(expand);
      }
      return;
    }
    if (["ArrowDown", "ArrowUp", "Home", "End"].indexOf(key) === -1) return;

    const links = visibleSideLinks();
    if (!links.length) return;
    const currentIndex = links.indexOf(document.activeElement);
    let nextIndex;
    if (key === "Home") {
      nextIndex = 0;
    } else if (key === "End") {
      nextIndex = links.length - 1;
    } else if (key === "ArrowDown") {
      nextIndex = currentIndex < 0 ? 0 : (currentIndex + 1) % links.length;
    } else {
      nextIndex = currentIndex <= 0 ? links.length - 1 : currentIndex - 1;
    }
    event.preventDefault();
    links[nextIndex].focus({ preventScroll: true });
    moveSidebarNeedle(links[nextIndex]);
  }

  function setActiveSidebar(key) {
    $$(".side-link").forEach(function (link) {
      const active = link.dataset.nav === key;
      link.classList.toggle("is-active", active);
      if (active) {
        link.setAttribute("aria-current", "page");
      } else {
        link.removeAttribute("aria-current");
      }
    });
    if (!sidebarHovering) moveSidebarNeedle();
  }

  function updatePageTitle() {
    const title = $("#page-title");
    if (!title) return;
    if (state.view === "dashboard") {
      title.textContent = isAdmin() ? "Admin Dashboard" : isViewer() ? "Faculty Directory" : "Dashboard";
    } else if (state.view === "form") {
      title.textContent = isAdmin() ? (state.formIsNew ? "Add Faculty" : "Edit Faculty") : "Profile Builder";
    } else {
      title.textContent = "Faculty Profile";
    }
  }

  function showView(name, keepScroll) {
    state.view = name;
    $$(".view").forEach(function (view) {
      view.hidden = view.dataset.view !== name;
    });
    updatePageTitle();
    updateSidebarItems();
    if (name === "dashboard") {
      setActiveSidebar("overview");
      renderDashboard();
      renderDirectory();
    }
    if (!keepScroll) {
      window.scrollTo({ top: 0, behavior: "auto" });
    }
    window.requestAnimationFrame(function () {
      window.setTimeout(updateScrollSpy, 80);
    });
  }

  function scrollToElement(element) {
    if (!element) return;
    const offset = 96;
    const top = window.scrollY + element.getBoundingClientRect().top - offset;
    window.scrollTo({ top: Math.max(0, top), behavior: REDUCED_MOTION ? "auto" : "smooth" });
  }

  function navigateToSection(key) {
    if (key === "overview") {
      showView("dashboard");
      return;
    }
    if (key === "directory" || key === "manage-faculty") {
      goToDirectory();
      return;
    }
    if (key === "my-profile") {
      const own = currentFaculty();
      if (own) {
        renderProfileView(own);
        showView("profile");
      }
      return;
    }
    if (key === "add-faculty") {
      if (isAdmin()) startFreshProfile();
      return;
    }
    if (key === "profile-overview") key = "overview";
    if (state.view === "dashboard") return;

    let target = document.getElementById(state.view + "-" + key);
    if (!target) target = document.getElementById("profile-" + key);
    if (!target) target = document.getElementById("form-" + key);
    if (target) {
      spyLockUntil = Date.now() + 1400;
      scrollToElement(target);
      setActiveSidebar(key === "overview" ? "profile-overview" : key);
    }
    if (window.innerWidth < 1280 && state.sidebarExpanded) {
      setSidebarExpanded(false);
    }
  }

  let scrollTicking = false;
  let spyLockUntil = 0;
  function updateScrollSpy() {
    const activeView = $(".view:not([hidden])");
    if (!activeView) return;
    if (state.view === "dashboard") {
      const directory = $(".directory-section");
      const atDirectory = directory && directory.getBoundingClientRect().top <= 220;
      setActiveSidebar(atDirectory ? "directory" : "overview");
      return;
    }
    if (Date.now() < spyLockUntil) return;
    const sections = $$("[data-sidebar-section]", activeView).filter(function (section) {
      return section.offsetParent !== null;
    });
    if (!sections.length) return;
    let current = sections[0].dataset.sidebarSection || "overview";
    const marker = 170;
    const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;
    if (atBottom) {
      current = sections[sections.length - 1].dataset.sidebarSection || current;
    } else {
      sections.forEach(function (section) {
        if (section.getBoundingClientRect().top <= marker) {
          current = section.dataset.sidebarSection || current;
        }
      });
    }
    setActiveSidebar(current === "overview" ? "profile-overview" : current);
  }

  function handleScroll() {
    if (scrollTicking) return;
    scrollTicking = true;
    window.requestAnimationFrame(function () {
      updateScrollSpy();
      scrollTicking = false;
    });
  }

  function updateSidebarBackdrop() {
    const backdrop = $("#sidebar-backdrop");
    if (!backdrop) return;
    const show = state.sidebarExpanded && window.innerWidth < 1280;
    if (show) {
      backdrop.hidden = false;
      window.requestAnimationFrame(function () {
        backdrop.classList.add("is-visible");
      });
    } else {
      backdrop.classList.remove("is-visible");
      backdrop.hidden = true;
    }
  }

  function setSidebarExpanded(expanded) {
    state.sidebarExpanded = !!expanded;
    const sidebar = $("#sidebar");
    const toggle = $("#sidebar-toggle");
    const icon = $("#sidebar-toggle-icon");
    const label = toggle ? $(".sidebar__toggle-label", toggle) : null;

    if (sidebar) sidebar.classList.toggle("is-expanded", state.sidebarExpanded);
    document.body.classList.toggle("sidebar-expanded", state.sidebarExpanded);
    if (toggle) {
      toggle.setAttribute("aria-expanded", state.sidebarExpanded ? "true" : "false");
      toggle.title = state.sidebarExpanded ? "Collapse sidebar" : "Expand sidebar";
    }
    if (icon) icon.className = state.sidebarExpanded ? "bi bi-chevron-left" : "bi bi-chevron-right";
    if (label) label.textContent = state.sidebarExpanded ? "Collapse" : "Expand";
    updateSidebarBackdrop();
    window.requestAnimationFrame(function () {
      layoutSidebarArc();
      moveSidebarNeedle();
    });
  }

  function updateRoleUI() {
    const role = ROLES[state.role] || ROLES.faculty;
    const icon = $("#role-indicator-icon");
    const label = $("#role-indicator-label");
    const detail = $("#role-indicator-detail");
    if (icon) icon.className = "bi " + role.icon;
    if (label) label.textContent = role.label;
    if (detail) {
      const own = currentFaculty();
      detail.textContent = isFaculty() && own && own.name ? own.name : role.short + " demo session";
    }

    const myProfile = $("#btn-my-profile");
    if (myProfile) myProfile.hidden = !isFaculty();
    const addFaculty = $("#btn-add-faculty");
    if (addFaculty) addFaculty.hidden = !isAdmin();
    const directoryAdd = $("#btn-add-faculty-directory");
    if (directoryAdd) directoryAdd.hidden = !isAdmin();
  }

  function updateSidebarItems() {
    $$("[data-nav-role]").forEach(function (link) {
      const allowed = link.dataset.navRole === "all" || link.dataset.navRole === state.role;
      link.hidden = !allowed;
    });
    const showSections = state.view === "profile" || state.view === "form";
    $$("[data-nav-section]").forEach(function (link) {
      link.hidden = !showSections;
    });
    const label = $("#sidebar-section-label");
    if (label) label.hidden = !showSections;
    const sidebar = $("#sidebar");
    if (sidebar) sidebar.classList.toggle("has-sections", showSections);
    window.requestAnimationFrame(function () {
      layoutSidebarArc();
      moveSidebarNeedle();
    });
  }

  function showRoleChoices() {
    const choices = $("#role-choices");
    const admin = $("#admin-login");
    const faculty = $("#faculty-login");
    if (choices) choices.hidden = false;
    if (admin) admin.hidden = true;
    if (faculty) faculty.hidden = true;
    const first = $("#role-choices .role-card");
    if (first) first.focus({ preventScroll: true });
  }

  function openRoleGate() {
    const gate = $("#role-gate");
    if (!gate) return;
    gateLastFocused = document.activeElement;
    gate.hidden = false;
    document.body.classList.add("gate-open");
    const close = $("#role-gate-close");
    if (close) close.hidden = !state.session;
    showRoleChoices();
  }

  function closeRoleGate() {
    const gate = $("#role-gate");
    if (!gate || gate.hidden || !state.session) return;
    gate.hidden = true;
    document.body.classList.remove("gate-open");
    if (gateLastFocused && typeof gateLastFocused.focus === "function") {
      gateLastFocused.focus({ preventScroll: true });
    }
  }

  function selectLoginRole(role) {
    if (role === "viewer") {
      completeLogin("viewer");
      return;
    }
    const choices = $("#role-choices");
    if (choices) choices.hidden = true;
    if (role === "admin") {
      const panel = $("#admin-login");
      if (panel) panel.hidden = false;
      const email = $("#admin-email");
      if (email) email.focus({ preventScroll: true });
      return;
    }
    if (role === "faculty") {
      const panel = $("#faculty-login");
      if (panel) panel.hidden = false;
      const email = $("#faculty-email");
      if (email) email.focus({ preventScroll: true });
    }
  }

  function completeLogin(role, facultyId) {
    if (!ROLES[role]) return;
    state.role = role;
    if (role === "faculty" && facultyId && findFaculty(facultyId)) {
      state.activeFacultyId = facultyId;
    }
    state.session = true;
    state.viewed = null;
    state.formIsNew = false;
    persistState();
    closeRoleGate();
    updateRoleUI();
    updateSidebarItems();
    renderDashboard();
    renderDirectory();
    fillForm(currentFaculty());
    showView("dashboard");
    const signedInFaculty = isFaculty() ? currentFaculty() : null;
    showToast(
      signedInFaculty && signedInFaculty.name
        ? "Signed in as " + signedInFaculty.name + "."
        : "Signed in as " + ROLES[role].label + ".",
      "success"
    );
  }

  function initReveal() {
    const elements = $$(".reveal");
    if (!elements.length) return;
    if (REDUCED_MOTION || !("IntersectionObserver" in window)) {
      elements.forEach(function (element) { element.classList.add("is-visible"); });
      return;
    }
    revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        revealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    elements.forEach(function (element) { revealObserver.observe(element); });
  }

  function viewProfileById(id) {
    const profile = findFaculty(id);
    if (!profile) {
      showToast("That faculty profile could not be found.", "error");
      return;
    }
    renderProfileView(profile);
    showView("profile");
    if (window.innerWidth < 1280 && state.sidebarExpanded) setSidebarExpanded(false);
  }

  function goToDirectory() {
    showView("dashboard", true);
    setActiveSidebar("directory");
    const heading = $(".directory-section");
    if (heading) scrollToElement(heading);
  }

  function clearFieldError(input) {
    const field = input.closest(".field");
    if (!field) return;
    field.classList.remove("is-invalid");
    const error = $(".field-error", field);
    if (error) error.remove();
    input.removeAttribute("aria-invalid");
  }

  function resetDirectoryFilters() {
    ["directory-search", "directory-department", "directory-designation"].forEach(function (id) {
      const field = $("#" + id);
      if (field) field.value = "";
    });
    renderDirectory();
  }

  function setupEvents() {
    document.addEventListener("click", function (event) {
      const navButton = event.target.closest("[data-nav]");
      if (navButton) {
        navigateToSection(navButton.dataset.nav);
        return;
      }

      const loginRoleButton = event.target.closest("[data-login-role]");
      if (loginRoleButton) {
        selectLoginRole(loginRoleButton.dataset.loginRole);
        return;
      }

      if (event.target.closest("[data-login-back]")) {
        showRoleChoices();
        return;
      }

      if (event.target.closest("#role-gate-close") || event.target.closest("[data-close-gate]")) {
        closeRoleGate();
        return;
      }

      const addButton = event.target.closest("[data-add-entry]");
      if (addButton) {
        addEntry(addButton.dataset.addEntry);
        return;
      }

      const removeButton = event.target.closest("[data-remove-entry]");
      if (removeButton) {
        removeEntry(removeButton);
        return;
      }

      const editProfileButton = event.target.closest("[data-edit-profile]");
      if (editProfileButton) {
        const target = findFaculty(editProfileButton.dataset.editProfile);
        if (target) openForm(target);
        return;
      }

      const viewButton = event.target.closest("[data-view-profile]");
      if (viewButton) {
        viewProfileById(viewButton.dataset.viewProfile);
        return;
      }

      const closeTrigger = event.target.closest("[data-close-modal]");
      if (closeTrigger) {
        closeModal();
        return;
      }

      const actionButton = event.target.closest("[data-action]");
      if (actionButton) {
        const action = actionButton.dataset.action;
        if (action === "edit-profile") {
          openForm(state.viewed || currentFaculty());
        } else if (action === "create-own") {
          startFreshProfile();
        } else if (action === "new-profile") {
          startFreshProfile();
        } else if (action === "view-owner") {
          renderProfileView(currentFaculty());
          showView("profile");
        } else if (action === "scroll-directory") {
          goToDirectory();
        } else if (action === "remove-photo") {
          state.draftPhoto = "";
          const input = $("#input-photo");
          if (input) input.value = "";
          renderPhotoPreview();
        }
      }
    });

    const toggle = $("#sidebar-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        setSidebarExpanded(!state.sidebarExpanded);
      });
    }

    const backdrop = $("#sidebar-backdrop");
    if (backdrop) {
      backdrop.addEventListener("click", function () {
        setSidebarExpanded(false);
      });
    }

    const sidebarEl = $("#sidebar");
    if (sidebarEl) {
      sidebarEl.addEventListener("keydown", handleSidebarKeydown);
      const nav = $(".sidebar__nav", sidebarEl);
      if (nav) {
        nav.addEventListener("pointerenter", function () {
          sidebarHovering = true;
        });
        nav.addEventListener("pointerover", function (event) {
          const link = event.target.closest ? event.target.closest(".side-link") : null;
          if (link) moveSidebarNeedle(link);
        });
        nav.addEventListener("pointerleave", function () {
          sidebarHovering = false;
          moveSidebarNeedle();
        });
        nav.addEventListener("focusin", function (event) {
          const link = event.target.closest ? event.target.closest(".side-link") : null;
          if (link) moveSidebarNeedle(link);
        });
      }
    }

    const brand = $("#sidebar-brand");
    if (brand) {
      brand.addEventListener("click", function (event) {
        event.preventDefault();
        showView("dashboard");
      });
    }

    const directoryButton = $("#btn-directory");
    if (directoryButton) directoryButton.addEventListener("click", goToDirectory);

    const switchRole = $("#btn-switch-role");
    if (switchRole) switchRole.addEventListener("click", openRoleGate);

    const addFacultyButton = $("#btn-add-faculty");
    if (addFacultyButton) addFacultyButton.addEventListener("click", startFreshProfile);

    const myProfileButton = $("#btn-my-profile");
    if (myProfileButton) {
      myProfileButton.addEventListener("click", function () {
        const own = currentFaculty();
        if (own) {
          renderProfileView(own);
          showView("profile");
        }
      });
    }

    const formBack = $("#form-back");
    if (formBack) formBack.addEventListener("click", function () { showView("dashboard"); });

    const cancelEdit = $("#btn-cancel-edit");
    if (cancelEdit) cancelEdit.addEventListener("click", function () { showView("dashboard"); });

    const profileBack = $("#profile-back");
    if (profileBack) {
      profileBack.addEventListener("click", function () {
        const own = currentFaculty();
        const backToDirectory = isAdmin() || isViewer() || !own || !state.viewed || state.viewed.id !== own.id;
        showView("dashboard");
        if (backToDirectory) {
          const heading = $(".directory-section");
          if (heading) scrollToElement(heading);
        }
      });
    }

    const saveButton = $("#btn-save-profile");
    if (saveButton) saveButton.addEventListener("click", saveProfile);

    const saveFormButton = $("#btn-save-form");
    if (saveFormButton) {
      saveFormButton.addEventListener("click", function () {
        if (!validateForm()) return;
        state.preview = collectForm();
        saveProfile();
      });
    }

    const directoryAdd = $("#btn-add-faculty-directory");
    if (directoryAdd) directoryAdd.addEventListener("click", startFreshProfile);

    const adminLogin = $("#admin-login");
    if (adminLogin) {
      adminLogin.addEventListener("submit", function (event) {
        event.preventDefault();
        const email = $("#admin-email");
        const password = $("#admin-password");
        if (!email || !password || !email.value.trim() || !password.value.trim()) {
          showToast("Enter your admin email and password to continue.", "error");
          if (email && !email.value.trim()) email.focus();
          return;
        }
        const value = email.value.trim().toLowerCase();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
          showToast("Enter a valid email address, for example name@ucen.ac.in.", "error");
          email.focus();
          email.select();
          return;
        }
        if (value !== "admin@ucen.ac.in") {
          if (findFacultyByEmail(value)) {
            showToast("This email belongs to a faculty account. Please use Faculty sign in.", "error");
          } else {
            showToast("This email is not registered as an admin account. Check the address or contact the administrator.", "error");
          }
          email.focus();
          email.select();
          return;
        }
        if (password.value !== "admin123") {
          showToast("Incorrect password for the admin account. Please try again.", "error");
          password.focus();
          password.select();
          return;
        }
        completeLogin("admin");
      });
    }

    const facultyLogin = $("#faculty-login");
    if (facultyLogin) {
      facultyLogin.addEventListener("submit", function (event) {
        event.preventDefault();
        const email = $("#faculty-email");
        const password = $("#faculty-password");
        if (!email || !password || !email.value.trim() || !password.value.trim()) {
          showToast("Enter your faculty email and password to continue.", "error");
          if (email && !email.value.trim()) email.focus();
          return;
        }
        const value = email.value.trim().toLowerCase();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
          showToast("Enter a valid email address, for example name@ucen.ac.in.", "error");
          email.focus();
          email.select();
          return;
        }
        if (value === "admin@ucen.ac.in") {
          showToast("This is an administrator account. Please use Admin / In-charge sign in.", "error");
          email.focus();
          email.select();
          return;
        }
        const profile = findFacultyByEmail(value);
        if (!profile) {
          showToast("No faculty account is registered with this email. Please register or contact the administrator.", "error");
          email.focus();
          email.select();
          return;
        }
        if (password.value !== "faculty123") {
          showToast("Incorrect password for this faculty account. Please try again.", "error");
          password.focus();
          password.select();
          return;
        }
        completeLogin("faculty", profile.id);
      });
    }

    $$("[data-toggle-password]").forEach(function (toggle) {
      toggle.addEventListener("click", function () {
        const target = $("#" + toggle.dataset.togglePassword);
        if (!target) return;
        const show = target.type === "password";
        target.type = show ? "text" : "password";
        toggle.setAttribute("aria-label", show ? "Hide password" : "Show password");
        toggle.setAttribute("aria-pressed", String(show));
        const icon = toggle.querySelector("i");
        if (icon) icon.className = show ? "bi bi-eye-slash" : "bi bi-eye";
      });
    });

    const resetButton = $("#reset-demo");
    if (resetButton) resetButton.addEventListener("click", resetDemo);

    const directoryReset = $("#directory-reset");
    if (directoryReset) directoryReset.addEventListener("click", resetDirectoryFilters);

    const emptyReset = $("#directory-empty-reset");
    if (emptyReset) emptyReset.addEventListener("click", resetDirectoryFilters);

    const removePhoto = $("#btn-remove-photo");
    if (removePhoto) {
      removePhoto.addEventListener("click", function () {
        state.draftPhoto = "";
        const input = $("#input-photo");
        if (input) input.value = "";
        renderPhotoPreview();
      });
    }

    const photoInput = $("#input-photo");
    if (photoInput) {
      photoInput.addEventListener("change", function () {
        if (photoInput.files && photoInput.files[0]) {
          handlePhotoFile(photoInput.files[0]);
        }
      });
    }

    const form = $("#profile-form");
    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!validateForm()) return;
        state.preview = collectForm();
        renderPreview(state.preview);
        openModal();
      });

      form.addEventListener("input", function (event) {
        if (event.target.matches("input, select, textarea")) {
          clearFieldError(event.target);
          updateFormCompletion();
          if (event.target.id === "p-name") renderPhotoPreview();
        }
      });

      form.addEventListener("change", function (event) {
        if (event.target.matches("input, select, textarea")) {
          updateFormCompletion();
        }
      });
    }

    const search = $("#directory-search");
    if (search) search.addEventListener("input", renderDirectory);

    ["directory-department", "directory-designation"].forEach(function (id) {
      const select = $("#" + id);
      if (select) select.addEventListener("change", renderDirectory);
    });

    window.addEventListener("scroll", handleScroll, { passive: true });

    window.addEventListener("resize", function () {
      updateSidebarBackdrop();
      updateScrollSpy();
      layoutSidebarArc();
      moveSidebarNeedle();
    });

    document.addEventListener("keydown", function (event) {
      const modal = $("#preview-modal");
      if (modal && !modal.hidden) {
        if (event.key === "Escape") {
          event.preventDefault();
          closeModal();
          return;
        }
        if (event.key === "Tab") {
          const focusable = $$("button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])", modal).filter(function (element) {
            return !element.disabled && element.offsetParent !== null;
          });
          if (!focusable.length) return;
          const first = focusable[0];
          const last = focusable[focusable.length - 1];
          if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
          } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
          }
        }
        return;
      }

      const gate = $("#role-gate");
      if (gate && !gate.hidden) {
        if (event.key === "Escape") {
          event.preventDefault();
          closeRoleGate();
        }
        return;
      }

      if (event.key === "Escape" && state.sidebarExpanded) {
        setSidebarExpanded(false);
      }
    });
  }

  function init() {
    const stored = loadState();
    state.role = stored.role;
    state.roster = stored.roster;
    state.activeFacultyId = stored.activeFacultyId;
    state.session = stored.session;
    const own = currentFaculty();
    state.formId = own ? own.id : "";
    state.draftPhoto = own ? own.photo : "";

    populateFilters();
    updateRoleUI();
    updateSidebarItems();
    fillForm(own);
    renderDashboard();
    renderDirectory();
    setActiveSidebar("overview");
    initReveal();
    setupEvents();
    updateScrollSpy();
    if (!state.session) openRoleGate();
  }

  init();
})();
