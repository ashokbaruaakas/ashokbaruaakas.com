<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from '@lucide/vue';
</script>

<template>
    <Head title="Ashok Barua Akas — Resume" />

    <div class="resume-page">
        <!-- Header -->
        <div class="resume-header">
            <h1>Ashok Barua Akas</h1>
            <div class="resume-title">
                Full-Stack Engineer - PHP · Laravel · TypeScript · Vue · Go
            </div>
            <div class="resume-contact-row">
                <span class="resume-contact-item">
                    <MapPin class="resume-contact-icon" />
                    Chattogram, Bangladesh
                </span>
                <span class="resume-contact-item">
                    <Phone class="resume-contact-icon" />
                    <a href="tel:+8801829853914">+880 1829 853914</a>
                </span>
                <span class="resume-contact-item">
                    <Mail class="resume-contact-icon" />
                    <a href="mailto:ashokbaruaakas@gmail.com"
                        >ashokbaruaakas@gmail.com</a
                    >
                </span>
                <span class="resume-contact-item">
                    <svg
                        class="resume-contact-icon"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"
                        />
                    </svg>
                    <a
                        href="https://github.com/ashokbaruaakas"
                        target="_blank"
                        rel="noopener"
                        >github.com/ashokbaruaakas</a
                    >
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="resume-body">
            <!-- Summary -->
            <div class="resume-section">
                <div class="resume-section-title">Professional Summary</div>
                <div class="resume-summary">
                    Full-stack developer with 7+ years of experience building
                    production web applications using Laravel, Vue, and
                    TypeScript. Strong background in enterprise-grade payment
                    systems, microservices architecture, and secure
                    HSM-integrated platforms. Currently building a 40,000-user
                    SaaS platform and integrating AI-augmented workflows to ship
                    faster.
                </div>
            </div>

            <!-- Skills -->
            <div class="resume-section">
                <div class="resume-section-title">Technical Skills</div>
                <div class="resume-skills-grid">
                    <div class="resume-skill-group">
                        <h4>Languages</h4>
                        <p>
                            PHP, TypeScript, JavaScript, Go, Rust, Python, SQL,
                            HTML, CSS
                        </p>
                    </div>
                    <div class="resume-skill-group">
                        <h4>Frameworks</h4>
                        <p>
                            Laravel, Vue.js, Nuxt.js, React, Next.js,
                            Inertia.js, Tailwind CSS
                        </p>
                    </div>
                    <div class="resume-skill-group">
                        <h4>Databases</h4>
                        <p>MySQL, PostgreSQL, Redis</p>
                    </div>
                    <div class="resume-skill-group">
                        <h4>DevOps &amp; Tools</h4>
                        <p>
                            Linux, Nginx, Docker, Git, GitHub Actions, VPS,
                            CI/CD, Elasticsearch, Graylog
                        </p>
                    </div>
                    <div class="resume-skill-group">
                        <h4>AI &amp; Agent Engineering</h4>
                        <p>
                            OpenClaw, MCP, LLM APIs, Prompt Engineering,
                            AI-Augmented Development
                        </p>
                    </div>
                </div>
            </div>

            <!-- Experience -->
            <div class="resume-section">
                <div class="resume-section-title">Experience</div>

                <div class="resume-exp-item">
                    <div class="resume-exp-header">
                        <span class="resume-role-group">
                            <span class="resume-role"
                                >Full Stack Developer</span
                            >
                            <span class="resume-sep"> - </span>
                            <span class="resume-company">Grow More Gaze</span>
                        </span>
                        <span class="resume-date">Oct 2023 — Present</span>
                    </div>
                    <ul class="resume-exp-bullets">
                        <li>
                            Built the company's core platform from scratch — a
                            multi-tenant application serving 40,000+ customers
                            with org portfolio, HRM, payroll, invoicing, and
                            internal email campaigns
                        </li>
                        <li>
                            Integrated multi-gateway payment collection across
                            PayPal, Stripe, Paddle, cryptocurrency,
                            PayProGlobal, bKash, and Nagad
                        </li>
                        <li>
                            Own the full software lifecycle: architecture,
                            development (Laravel/Vue/TypeScript), database
                            design, server administration, and CI/CD deployment
                        </li>
                        <li>
                            Lead technical planning, coordinate team efforts,
                            and establish development workflows and best
                            practices
                        </li>
                        <li>
                            Integrate AI agent workflows (OpenClaw, LLM APIs)
                            into development and operations processes
                        </li>
                    </ul>
                </div>

                <div class="resume-exp-item">
                    <div class="resume-exp-header">
                        <span class="resume-role-group">
                            <span class="resume-role"
                                >Senior Software Engineer</span
                            >
                            <span class="resume-sep"> - </span>
                            <span class="resume-company"
                                >Softrobotics Bangladesh Ltd</span
                            >
                        </span>
                        <span class="resume-date">Sep 2021 — Sep 2023</span>
                    </div>
                    <ul class="resume-exp-bullets">
                        <li>
                            Developed SiPay, a banking platform serving top-tier
                            Turkish financial institutions, featuring merchant
                            banking, mobile banking, and comprehensive payment
                            modules
                        </li>
                        <li>
                            Implemented diverse payment flows — link payments,
                            P2P/B2B transfers, QR payments, bill payments, POS
                            machine integration, cash out, and deposit/withdraw
                            operations
                        </li>
                        <li>
                            Built scalable backend services using microservices
                            architecture with Elasticsearch and Graylog for
                            centralized logging and monitoring
                        </li>
                        <li>
                            Integrated HSM devices for secure cryptographic
                            operations and key management across payment
                            processing
                        </li>
                        <li>
                            Served as team lead for final 4 months —
                            coordinating development, conducting code reviews,
                            and mentoring junior engineers
                        </li>
                    </ul>
                </div>

                <div class="resume-exp-item">
                    <div class="resume-exp-header">
                        <span class="resume-role-group">
                            <span class="resume-role"
                                >Full-stack Web Developer &amp; Designer</span
                            >
                            <span class="resume-sep"> - </span>
                            <span class="resume-company">Stellar BD Ltd</span>
                        </span>
                        <span class="resume-date">Oct 2019 — Aug 2021</span>
                    </div>
                    <ul class="resume-exp-bullets">
                        <li>
                            Built HRM and payroll systems integrated with IoT
                            devices — fingerprint scanners, temperature
                            detectors, smart door locks, and digital notice
                            boards
                        </li>
                        <li>
                            Implemented real-time data synchronization via MQTT
                            for IoT device communication and management
                        </li>
                        <li>
                            Developed client websites and automation scripts
                            using PHP, JavaScript, Python, and Web2py
                        </li>
                        <li>
                            Designed responsive interfaces and managed hosting
                            environments end-to-end
                        </li>
                    </ul>
                </div>

                <div class="resume-exp-item">
                    <div class="resume-exp-header">
                        <span class="resume-role-group">
                            <span class="resume-role"
                                >Full-stack Web Developer</span
                            >
                            <span class="resume-sep"> - </span>
                            <span class="resume-company"
                                >Multiplex Web Design</span
                            >
                        </span>
                        <span class="resume-date">Mar 2018 — Sep 2019</span>
                    </div>
                    <ul class="resume-exp-bullets">
                        <li>
                            Delivered custom web applications for SMB clients,
                            handling end-to-end project lifecycles from
                            requirements to deployment
                        </li>
                        <li>
                            Built across the full stack using PHP, JavaScript,
                            MySQL, and responsive design
                        </li>
                        <li>
                            Collaborated directly with clients on scoping,
                            timelines, and delivery
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Projects -->
            <div class="resume-section">
                <div class="resume-section-title">Projects</div>

                <div class="resume-proj-item">
                    <div class="resume-proj-header">
                        <span class="resume-proj-name">bizztechsz.com</span>
                        <span class="resume-proj-links">
                            <a
                                href="https://bizztechsz.com"
                                target="_blank"
                                rel="noopener"
                                >Live</a
                            >
                        </span>
                    </div>
                    <div class="resume-proj-desc">
                        Client business platform for managing worldwide bulk
                        order quotes and company portfolios. Built and
                        maintained part-time alongside full-time role.
                    </div>
                    <div class="resume-proj-tech">
                        <span>Laravel</span>
                        <span>Vue.js</span>
                        <span>TypeScript</span>
                        <span>Tailwind</span>
                        <span>MySQL</span>
                    </div>
                </div>

                <div class="resume-proj-item">
                    <div class="resume-proj-header">
                        <span class="resume-proj-name">clawkit</span>
                        <span class="resume-proj-links">
                            <a
                                href="https://github.com/ashokbaruaakas/clawkit"
                                target="_blank"
                                rel="noopener"
                                >GitHub</a
                            >
                            <a
                                href="https://ghcr.io/ashokbaruaakas/clawkit"
                                target="_blank"
                                rel="noopener"
                                >GHCR</a
                            >
                        </span>
                    </div>
                    <div class="resume-proj-desc">
                        Docker wrapper image around OpenClaw with Linuxbrew and
                        development tooling. Published via automated CI/CD with
                        multi-tag versioning to GHCR.
                    </div>
                    <div class="resume-proj-tech">
                        <span>Docker</span>
                        <span>GitHub Actions</span>
                        <span>CI/CD</span>
                        <span>OpenClaw</span>
                        <span>GHCR</span>
                    </div>
                </div>

                <div class="resume-proj-item">
                    <div class="resume-proj-header">
                        <span class="resume-proj-name">LaraVibe-Vue</span>
                        <span class="resume-proj-links">
                            <a
                                href="https://github.com/softpulze/laravibe-vue"
                                target="_blank"
                                rel="noopener"
                                >GitHub</a
                            >
                        </span>
                    </div>
                    <div class="resume-proj-desc">
                        Open-source Laravel 13 starter kit with Vue 3 +
                        Inertia.js v3, SSR, authentication, account management,
                        admin area, and type-safe route helpers. Published under
                        @softpulze.
                    </div>
                    <div class="resume-proj-tech">
                        <span>Laravel 13</span>
                        <span>Vue 3</span>
                        <span>Inertia.js</span>
                        <span>TypeScript</span>
                        <span>Tailwind</span>
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="resume-section">
                <div class="resume-section-title">Education</div>
                <div class="resume-edu-grid">
                    <div class="resume-edu-item">
                        <span>
                            <span class="resume-degree"
                                >BSc in Computer Science</span
                            >
                            <span class="resume-school">
                                — East Delta University, Chittagong</span
                            >
                        </span>
                        <span class="resume-date">2018 — 2021</span>
                    </div>
                    <div class="resume-edu-item">
                        <span>
                            <span class="resume-degree"
                                >Diploma in Computer Science</span
                            >
                            <span class="resume-school">
                                — Bangladesh Sweden Polytechnic Institute,
                                Rangamati</span
                            >
                        </span>
                        <span class="resume-date">2013 — 2017</span>
                    </div>
                </div>
            </div>

            <hr />

            <!-- Languages -->
            <div class="resume-section">
                <div class="resume-section-title">Languages</div>
                <div class="resume-lang-list">
                    <span
                        >Bengali
                        <span class="resume-lang-level">(Native)</span></span
                    >
                    <span
                        >English
                        <span class="resume-lang-level">(Fluent)</span></span
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.resume-page {
    max-width: 210mm;
    margin: 0 auto;
    background: #ffffff;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(0, 0, 0, 0.04);
    border-radius: 8px;
    overflow: hidden;
}

.resume-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #f8fafc;
    padding: 28px 36px 22px;
}

.resume-header h1 {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 24pt;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin-bottom: 3px;
}

.resume-title {
    font-size: 10.5pt;
    color: #94a3b8;
    margin-bottom: 10px;
}

.resume-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 20px;
    font-size: 8.5pt;
    color: #cbd5e1;
}

.resume-contact-row a {
    color: #34d399;
    text-decoration: none;
}

.resume-contact-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.resume-contact-icon {
    width: 11px;
    height: 11px;
    flex-shrink: 0;
}

.resume-body {
    padding: 24px 36px 28px;
}

.resume-section {
    margin-bottom: 18px;
}

.resume-section-title {
    font-size: 10.5pt;
    font-weight: 700;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-bottom: 3px;
    border-bottom: 2px solid #10b981;
    margin-bottom: 8px;
}

.resume-summary {
    font-size: 9.5pt;
    color: #475569;
    line-height: 1.6;
}

.resume-skills-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.resume-skill-group {
    flex: 1;
    min-width: 140px;
}

.resume-skill-group h4 {
    font-size: 8pt;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.resume-skill-group p {
    font-size: 9pt;
    color: #1e293b;
    line-height: 1.5;
}

.resume-exp-item {
    margin-bottom: 12px;
}

.resume-exp-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    margin-bottom: 1px;
}

.resume-role {
    font-size: 10pt;
    font-weight: 600;
    color: #0f172a;
}

.resume-company {
    font-size: 9pt;
    font-weight: 500;
    color: #10b981;
}

.resume-role-group {
    display: inline-flex;
    align-items: baseline;
}

.resume-sep {
    font-size: 9pt;
    color: #64748b;
    padding: 0 4px;
}

.resume-date {
    font-size: 8pt;
    color: #94a3b8;
    white-space: nowrap;
}

.resume-exp-bullets {
    list-style: none;
    padding-left: 0;
}

.resume-exp-bullets li {
    font-size: 8.5pt;
    color: #475569;
    padding-left: 14px;
    position: relative;
    margin-bottom: 1px;
}

.resume-exp-bullets li::before {
    content: '\25B8';
    position: absolute;
    left: 0;
    color: #10b981;
}

.resume-proj-item {
    margin-bottom: 9px;
}

.resume-proj-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
}

.resume-proj-name {
    font-size: 9.5pt;
    font-weight: 600;
    color: #0f172a;
}

.resume-proj-links {
    font-size: 8pt;
}

.resume-proj-links a {
    color: #10b981;
    text-decoration: none;
}

.resume-proj-desc {
    font-size: 8.5pt;
    color: #475569;
    margin-top: 1px;
}

.resume-proj-tech {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 2px;
}

.resume-proj-tech span {
    font-size: 7pt;
    background: #d1fae5;
    color: #065f46;
    padding: 1px 7px;
    border-radius: 3px;
    font-weight: 500;
}

.resume-edu-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.resume-edu-item {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
}

.resume-degree {
    font-size: 9pt;
    font-weight: 600;
    color: #0f172a;
}

.resume-school {
    font-size: 8.5pt;
    color: #475569;
}

.resume-lang-list {
    display: flex;
    gap: 20px;
}

.resume-lang-list span {
    font-size: 9pt;
    color: #475569;
}

.resume-lang-level {
    color: #94a3b8;
}

.resume-body hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 12px 0;
}

@media print {
    .resume-page {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
    }

    .resume-header {
        padding: 24px 28px 18px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .resume-body {
        padding: 18px 28px 22px;
    }

    .resume-proj-tech span {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .resume-page a {
        text-decoration: none !important;
    }
}

@media screen and (max-width: 600px) {
    .resume-header {
        padding: 16px 14px;
    }

    .resume-body {
        padding: 14px;
    }

    .resume-header h1 {
        font-size: 20pt;
    }

    .resume-skills-grid {
        flex-direction: column;
    }

    .resume-exp-header {
        flex-direction: column;
    }

    .resume-contact-row {
        flex-direction: column;
        gap: 3px;
    }
}
</style>
