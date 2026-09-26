<?php

namespace App\Services;

class ColoredCowContext
{
    public static function getValues(): array
    {
        return [
            'Take Responsibility' => [
                'description' => 'Ownership beyond assigned scope, proactive contribution. By joining in the good work and taking responsibility to contribute, we keep our workplace happy, motivated and sustainable.',
                'evaluation_signals' => [
                    'Code that goes beyond minimum requirements',
                    'Proactive improvements without being asked',
                    'Complete ownership of features end-to-end',
                    'Taking on tasks outside immediate scope',
                    'Accountability when things go wrong',
                ],
            ],
            'Build Remarkable' => [
                'description' => 'Attention to detail, craft in how and why things are built. Remarkable is present in small, everyday acts.',
                'evaluation_signals' => [
                    'Clean, well-structured code with attention to naming',
                    'Thoughtful architecture decisions',
                    'Code that shows care for maintainability',
                    'Documentation that explains the why, not just the what',
                    'Consistent coding style and patterns',
                ],
            ],
            'Always Learning' => [
                'description' => 'Growth mindset, learning from failure, continuous improvement. We learn as a group, we learn from each other.',
                'evaluation_signals' => [
                    'Evidence of learning new technologies over time',
                    'Commit messages that show learning progression',
                    'Responsiveness to feedback in PR reviews',
                    'Experiments and exploration in side projects',
                    'Self-awareness about limitations',
                ],
            ],
            'Respect and Make Each Other Successful' => [
                'description' => 'Collaboration, helping others, team-first thinking. We take time to help others even if it requires us to go out of our way.',
                'evaluation_signals' => [
                    'Helpful code comments and documentation',
                    'Clear README files that help others get started',
                    'Constructive issue responses',
                    'Code designed for team readability',
                    'Contributions to shared projects',
                ],
            ],
            'Plentiful for Everyone' => [
                'description' => 'Generosity with knowledge, not hoarding. The team grows on the idea of a collective pool that has plenty for anyone\'s needs.',
                'evaluation_signals' => [
                    'Open-source contributions',
                    'Sharing knowledge through documentation',
                    'Reusable code patterns',
                    'Teaching through code examples',
                    'Community engagement',
                ],
            ],
            'Freedom and Creativity' => [
                'description' => 'Independent thinking, creative problem solving. Allows each of us to make impact in our unique way.',
                'evaluation_signals' => [
                    'Creative solutions to complex problems',
                    'Independent technical decision-making',
                    'Novel approaches to common challenges',
                    'Side projects that show passion',
                    'Thinking outside conventional patterns',
                ],
            ],
        ];
    }

    public static function getTechStack(): array
    {
        return [
            'backend' => [
                'primary' => ['PHP', 'Laravel', 'Elixir', 'WordPress'],
                'secondary' => ['Python', 'Django'],
                'api_patterns' => ['REST APIs', 'Background jobs', 'Authentication', 'Domain modeling'],
            ],
            'frontend' => [
                'primary' => ['React', 'Vue.js'],
                'patterns' => ['Component-based UI', 'State management', 'Accessibility', 'Modern build tooling'],
            ],
            'database' => [
                'primary' => ['PostgreSQL', 'MySQL'],
                'caching' => ['Redis'],
                'skills' => ['Relational design', 'Migrations', 'Performance tuning', 'Reporting and analytics'],
            ],
            'cloud' => [
                'provider' => 'AWS',
                'services' => ['ECS Fargate', 'Lambda', 'ALB', 'NLB', 'CloudFront', 'WAF', 'RDS', 'ElastiCache'],
                'devops' => ['Docker', 'GitHub Actions', 'Terraform', 'Ansible'],
                'monitoring' => ['Sentry', 'Cloud-native monitoring'],
            ],
            'practices' => [
                'Automated testing',
                'Integration and regression testing',
                'CI/CD pipelines',
                'Code review',
                'Infrastructure as code',
            ],
            'system_types' => [
                'Multi-tenant SaaS platforms',
                'Long-lived, evolving systems',
                'Open-source platforms',
                'Data and analytics systems',
                'Cloud-native systems',
            ],
        ];
    }

    public static function getHiringPhilosophy(): string
    {
        return 'We hire for judgment, not credentials. One or two students out of every 30-35 pass our filter. Those are the people we grow. No knowledge loss. No handover gaps. Senior team averages 7+ years tenure. The person who starts your project is the person who grows it.';
    }

    public static function getCompanyProfile(): string
    {
        return <<<'EOT'
ColoredCow is a 25-person custom software development company based in Uttarakhand and Gurgaon, India, with 11 years of delivery history. We build complex, long-lived systems for social sector, healthcare, education, and agency clients.

We are bootstrapped, deliberately. We work as a responsible partner: we clarify early, decide transparently, and stay accountable when work gets hard.

Care means deep understanding first. Craft means making decisions that age well. Ownership means accountability beyond launch.

Our senior team averages 7+ years tenure. Every developer on the team works with AI tools daily. The productivity gain flows into proactive recommendations, strategic advisory, and domain depth.
EOT;
    }

    public static function getTakeHomeContract(): string
    {
        return <<<'EOT'
We do not run DSA rounds or whiteboard puzzles. A take-home assignment is a real, small project that reflects the kind of work you would actually do here. Use whatever tools you want, including AI. We are looking at how you approached it, how you communicated, and what decisions you made. We are not testing if you can solve problems without help — we are testing if you can solve problems well using everything available to you. AI used as a thinking partner is expected; AI used to skip understanding is the opposite of what we value.

We look for people who: care about the problem, not just the ticket; figure things out and leave a trail (document it, suggest a fix, leave a note); communicate in writing naturally; ship steadily in small consistent progress; follow up on their own work after deployment.

We also look for values: Take Responsibility, Build Remarkable, Always Learning, Respect and Make Each Other Successful, Plentiful for Everyone, Freedom and Creativity.
EOT;
    }

    public static function getPortfolio(): array
    {
        return [
            'intro' => 'These are real systems ColoredCow has built and sustained, written close to the work. Briefs should be anonymized by default: allude to the arc of the work (sector + system shape) without naming the client, matching ColoredCow\'s own practice of "a real anonymized brief from a current client engagement."',
            'archetypes' => [
                [
                    'name' => 'Plio',
                    'anonymized_reference' => 'an interactive-video learning platform for an education NGO',
                    'sector' => 'Education · NGO',
                    'stack' => ['React', 'Python', 'Django', 'multi-tenant database', 'WhatsApp reporting'],
                    'shape' => 'A video-first learning platform: authoring editor, learner-facing player, analytics pipeline, and WhatsApp nudges for teachers. Started as UI/UX work and grew into holding the multi-tenant infrastructure and reporting.',
                    'what_working_here_means' => 'Making a product feel lighter for creators while keeping reporting dependable for teachers who rely on it every Monday morning.',
                ],
                [
                    'name' => 'Goonj',
                    'anonymized_reference' => 'a high-volume donor and field-worker CRM for a large non-profit',
                    'sector' => 'Social Sector · Tech4Good',
                    'stack' => ['CiviCRM', 'WordPress', 'PHP'],
                    'shape' => 'A CRM for 500,000+ contacts, one system for every field worker, moved off per-seat licensing to CiviCRM.',
                    'what_working_here_means' => 'Performance at national scale without slowing the field teams or the public site.',
                ],
                [
                    'name' => 'MFM (D2C meals)',
                    'anonymized_reference' => 'a D2C e-commerce platform for a US creative agency client',
                    'sector' => 'Agencies · D2C E-commerce',
                    'stack' => ['WooCommerce', 'WordPress', 'PHP', 'performance engineering'],
                    'shape' => 'A subscription e-commerce system whose revenue runs through it: order velocity grew from ~350 to 8,000+ orders a month.',
                    'what_working_here_means' => 'Engineering whose every bug is a revenue event; shipping promises that keep even when tomorrow is a holiday.',
                ],
                [
                    'name' => 'Aam Digital',
                    'anonymized_reference' => 'an open-source, offline-first case-management system for NGOs',
                    'sector' => 'Social Sector · Open Source · Digital Public Good',
                    'stack' => ['Angular', 'Ionic', 'Java/Kotlin', 'offline-first sync'],
                    'shape' => 'A certified Digital Public Good run by 50+ NGOs across four continents — a platform we steward, not a product we own.',
                    'what_working_here_means' => 'Building for an ecosystem you do not own, dependable for field staff in low-connectivity settings.',
                ],
                [
                    'name' => 'Dost Education',
                    'anonymized_reference' => 'a field-serving voice and analytics platform for early-childhood education',
                    'sector' => 'Early-childhood education · Tech4Good',
                    'stack' => ['IVR', 'RapidPro', 'KooKoo', 'analytics', 'conversational AI voice'],
                    'shape' => 'IVR delivery to 130,000+ caregivers, ~70% cloud-cost reduction, now a conversational-Hindi AI voice agent.',
                    'what_working_here_means' => 'Holding the technology function so a mission-focused team can focus on its mission.',
                ],
                [
                    'name' => 'CII',
                    'anonymized_reference' => 'the AWS estate of an industry body',
                    'sector' => 'Public Sector · Industry body',
                    'stack' => ['AWS', 'ECS', 'RDS', 'CloudFront', 'WAF', 'security'],
                    'shape' => 'A seven-year partnership: building apps and sites, then running the servers behind all of them, protecting every public site.',
                    'what_working_here_means' => 'Picking up the phone when something breaks nobody planned for.',
                ],
                [
                    'name' => 'Glific',
                    'anonymized_reference' => 'a two-way WhatsApp communication platform for non-profits',
                    'sector' => 'Social Sector · Tech4Good',
                    'stack' => ['Elixir', 'Phoenix', 'React', 'WhatsApp Business API'],
                    'shape' => 'Two-way communication between organizations and their beneficiaries over WhatsApp.',
                    'what_working_here_means' => 'Reliable messaging at meaningful scale for field programmes.',
                ],
                [
                    'name' => 'Kotahi / Ketida',
                    'anonymized_reference' => 'an open-source manuscript submission and book production system',
                    'sector' => 'Open Source · Publishing',
                    'stack' => ['Java', 'React', 'open-source stewardship'],
                    'shape' => 'Self-hosted manuscript submission and single-source book production platforms, open source.',
                    'what_working_here_means' => 'Stewardship of open systems: keeping contribution paths warm and the platform standing over years.',
                ],
                [
                    'name' => 'Portal',
                    'anonymized_reference' => 'our own hub-and-spoke operations platform (internal)',
                    'sector' => 'Internal · Operations',
                    'stack' => ['Laravel', 'PHP', 'GSuite', 'multi-tenant hub-and-spoke'],
                    'shape' => 'ColoredCow\'s own operations platform for managing organizational operations and data.',
                    'what_working_here_means' => 'Dogfooding: building and maintaining the system we run on ourselves.',
                ],
                [
                    'name' => 'Tehri Telemedicine',
                    'anonymized_reference' => 'a telemedicine initiative with a state government',
                    'sector' => 'Public Sector · Healthcare',
                    'stack' => ['web platform', 'video consultation'],
                    'shape' => 'A government-of-Uttarakhand telemedicine initiative bringing rural healthcare online.',
                    'what_working_here_means' => 'Software in a regulated context where reliability is a public trust.',
                ],
            ],
        ];
    }

    public static function getWorkMethods(): string
    {
        return <<<'EOT'
How ColoredCow builds: automated testing, integration and regression testing, CI/CD pipelines, code review (including label-triggered AI-assisted code review on pull requests, comment-only, guided by a per-repo code-review-guidelines doc), infrastructure as code, and engineering hygiene — tests, CI, docs, and code that is review-ready. We build software our clients can run without us. We measure before touching anything. Everything we ship is documented so the next person does not repeat the struggle.
EOT;
    }

    public static function getFullContext(): array
    {
        return [
            'company' => static::getCompanyProfile(),
            'values' => static::getValues(),
            'tech_stack' => static::getTechStack(),
            'hiring_philosophy' => static::getHiringPhilosophy(),
            'take_home_contract' => static::getTakeHomeContract(),
            'portfolio' => static::getPortfolio(),
            'work_methods' => static::getWorkMethods(),
        ];
    }
}
