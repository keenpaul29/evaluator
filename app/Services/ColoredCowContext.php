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
                'primary' => ['PHP', 'Laravel', 'Elixir'],
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

    public static function getFullContext(): array
    {
        return [
            'company' => static::getCompanyProfile(),
            'values' => static::getValues(),
            'tech_stack' => static::getTechStack(),
            'hiring_philosophy' => static::getHiringPhilosophy(),
        ];
    }
}
