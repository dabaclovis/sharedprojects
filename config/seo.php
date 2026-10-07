<?php

return [
    'brand' => 'Brotherfall',
    'defaults' => [
        'title' => 'Brotherfall Community Hub | Articles, Tools and Resources',
        'description' => 'Explore practical online tools, community articles, product discoveries and resources for everyday work and planning.',
        'keywords' => 'community articles, online tools, product recommendations, planning resources',
    ],

    'routes' => [
        'home' => [
            'title' => 'Brotherfall Community Hub | Free Tools, Articles and Resources',
            'description' => 'Discover free online tools, useful community articles, product recommendations and practical business resources in one place.',
            'keywords' => 'free online tools, community articles, business resources, product recommendations',
        ],
        'pages.index' => [
            'title' => 'Brotherfall | Free Online Tools, Tutorials & Useful Resources',
            'description' => 'Brotherfall offers free online tools, practical tutorials, guides, reviews and useful resources for everyday work, technology, business and life.',
            'keywords' => 'online services, free calculators, writing tools, community stories',
        ],
        'pages.articles' => [
            'title' => 'Community Articles, Ideas and Stories | Brotherfall',
            'description' => 'Read useful community-written articles, fresh ideas and practical stories across business, technology and everyday life.',
            'keywords' => 'community articles, practical ideas, stories, business articles, technology articles',
        ],
        'pages.products' => [
            'title' => 'Recommended Products and Useful Finds | Brotherfall',
            'description' => 'Explore community product recommendations for everyday life and work, with practical benefits, limitations and clear affiliate disclosures.',
            'keywords' => 'product recommendations, useful products, affiliate products, product discoveries',
        ],
        'pages.quotes' => [
            'title' => 'Inspirational Quotes and Community Wisdom | Brotherfall',
            'description' => 'Browse, search and share memorable quotes, thoughtful sayings and community-contributed words of wisdom.',
            'keywords' => 'inspirational quotes, famous sayings, community quotes, words of wisdom',
        ],
        'pages.about' => [
            'title' => 'About the Brotherfall Community and Tools Platform',
            'description' => 'Learn how Brotherfall brings together helpful tools, original community content and practical resources in one accessible platform.',
            'keywords' => 'about Brotherfall, community platform, free online tools, practical resources',
        ],
        'pages.contact' => [
            'title' => 'Contact Brotherfall | Questions, Feedback and Support',
            'description' => 'Contact the Brotherfall team with questions, feedback, support requests or ideas for improving our tools and community resources.',
            'keywords' => 'contact Brotherfall, customer support, website feedback, help request',
        ],
        'pages.policy' => [
            'title' => 'Privacy, Cookies and Advertising Policy | Brotherfall',
            'description' => 'Learn how Brotherfall handles personal information, cookies, advertising choices, affiliate links and community content.',
            'keywords' => 'privacy policy, advertising cookies, consent, affiliate disclosure, community content',
        ],
        'pages.business' => [
            'title' => 'Website Audits and Sponsorship Services | Brotherfall',
            'description' => 'Request a professional website audit or explore transparent sponsorship opportunities for reaching the Brotherfall community.',
            'keywords' => 'website audit services, SEO review, sponsorship opportunities, website consulting',
        ],
        'pages.seo-audit' => [
            'title' => 'Free SEO Audit Tool | Check Your Web Page',
            'description' => 'Run a free technical SEO audit to check page titles, descriptions, headings, links, indexing signals and common issues.',
            'keywords' => 'free SEO audit, SEO checker, website analysis, technical SEO tool',
        ],
        'pages.web-crawler' => [
            'title' => 'Free Website Crawler and Link Checker | Brotherfall',
            'description' => 'Crawl your website to discover pages, inspect links, identify crawl barriers and review essential technical signals.',
            'keywords' => 'website crawler, link checker, crawl website, technical SEO crawler',
        ],
        'pages.quote-builder' => [
            'title' => 'Free Freelance Quote and Estimate Builder | Brotherfall',
            'description' => 'Create a clear freelance project estimate with tasks, hours, rates, expenses, contingency and deposit calculations.',
            'keywords' => 'freelance quote builder, project estimate calculator, pricing calculator, client quote',
        ],
        'pages.text-toolkit' => [
            'title' => 'Free Text Toolkit | Clean, Format and Count Text',
            'description' => 'Clean and format text, change letter case, remove duplicate lines and check word, character and reading-time totals.',
            'keywords' => 'text formatter, text cleaner, remove duplicate lines, case converter, word count',
        ],
        'pages.percentage-calculator' => [
            'title' => 'Free Percentage Calculator | Quick Results',
            'description' => 'Calculate percentages, percentage changes and proportions quickly with a simple, accurate online calculator.',
            'keywords' => 'percentage calculator, percent change calculator, calculate percentage, online calculator',
        ],
        'pages.unit-converter' => [
            'title' => 'Free Unit Converter | Length and Weight',
            'description' => 'Convert common length and weight measurements quickly with a straightforward, accurate online unit converter.',
            'keywords' => 'unit converter, length converter, weight converter, measurement conversion',
        ],
        'pages.date-difference' => [
            'title' => 'Date Difference Calculator | Days Between Dates',
            'description' => 'Calculate the exact time between two dates in days, weeks and calendar intervals, with an optional inclusive count.',
            'keywords' => 'date difference calculator, days between dates, date duration, calendar calculator',
        ],
        'pages.age-calculator' => [
            'title' => 'Free Age Calculator | Exact Age and Time Lived',
            'description' => 'Calculate an exact age and view total years, months, weeks, days, hours, minutes and seconds lived.',
            'keywords' => 'age calculator, exact age, birthday calculator, how old am I',
        ],
        'pages.word-counter' => [
            'title' => 'Free Word and Character Counter | Brotherfall',
            'description' => 'Count words, characters, non-space characters and estimated reading time for essays, posts and documents.',
            'keywords' => 'word counter, character counter, reading time calculator, text length checker',
        ],
        'pages.timezone-converter' => [
            'title' => 'Time Zone Converter | Compare Local Times',
            'description' => 'Convert dates and times between global time zones with daylight-saving and ambiguous-time validation.',
            'keywords' => 'time zone converter, world time converter, convert time zones, daylight saving time',
        ],

        'auth.login' => ['title' => 'Sign In to Your Brotherfall Account', 'description' => 'Sign in securely to manage your articles, products, calendar and account settings.', 'keywords' => 'Brotherfall login, account sign in, member login'],
        'auth.register' => ['title' => 'Create Your Free Brotherfall Account', 'description' => 'Create a Brotherfall account to contribute articles, manage recommendations and organize your personal calendar.', 'keywords' => 'create account, Brotherfall registration, community membership'],
        'users.index' => ['title' => 'Your Content Dashboard | Brotherfall', 'description' => 'Review your content activity, publication status, products and upcoming events.', 'keywords' => 'user dashboard, content dashboard, account overview'],
        'users.articles' => ['title' => 'Manage Your Articles | Brotherfall', 'description' => 'Write, revise, submit and manage your community articles from one workspace.', 'keywords' => 'manage articles, write articles, content workspace'],
        'users.products' => ['title' => 'Manage Your Product Recommendations | Brotherfall', 'description' => 'Create and maintain your product recommendations, images, prices and publication status.', 'keywords' => 'manage products, affiliate recommendations, product listings'],
        'users.calendar' => ['title' => 'Your Personal Calendar and Weekly Planner | Brotherfall', 'description' => 'Plan appointments and activities across monthly and weekly calendar views in your preferred time zone.', 'keywords' => 'personal calendar, weekly planner, event organizer'],
        'users.profile' => ['title' => 'Your Brotherfall Profile', 'description' => 'Review your Brotherfall account identity and profile information.', 'keywords' => 'user profile, Brotherfall account, profile details'],
        'users.setting' => ['title' => 'Account and Security Settings | Brotherfall', 'description' => 'Update your account details, email address and password securely.', 'keywords' => 'account settings, security settings, change password'],

        'admins.index' => ['title' => 'Administration Dashboard | Brotherfall', 'description' => 'Monitor users, content, products, events, quotes and messages across the Brotherfall platform.', 'keywords' => 'admin dashboard, content administration, platform management'],
        'admins.ratings' => ['title' => 'Application Ratings and Feedback | Brotherfall Admin', 'description' => 'Review private application ratings and user feedback for service improvement.', 'keywords' => 'application ratings, user feedback, admin reviews'],
        'admins.rewards' => ['title' => 'Content Rewards Management | Brotherfall Admin', 'description' => 'Manage the contributor reward fund and issue eligible article and quote rewards.', 'keywords' => 'content rewards, contributor payments, reward management'],
        'admins.users' => ['title' => 'User Account Management | Brotherfall Admin', 'description' => 'Create, review, update, suspend and manage Brotherfall user accounts and permissions.', 'keywords' => 'user management, account administration, user permissions'],
        'admins.articles' => ['title' => 'Article Moderation | Brotherfall Admin', 'description' => 'Review, approve, archive, restore and provide feedback on community articles.', 'keywords' => 'article moderation, content review, publishing workflow'],
        'admins.products' => ['title' => 'Product Moderation | Brotherfall Admin', 'description' => 'Review, approve, archive and restore submitted product recommendations.', 'keywords' => 'product moderation, affiliate review, product approval'],
        'admins.calendar' => ['title' => 'Event Administration | Brotherfall Admin', 'description' => 'Review and manage scheduled or cancelled user events across the platform.', 'keywords' => 'event administration, calendar management, scheduled events'],
        'admins.website-audits' => ['title' => 'Website Audit Orders | Brotherfall Admin', 'description' => 'Manage website audit requests, payments, findings and completed client reports.', 'keywords' => 'website audit orders, audit workflow, client reports'],
        'admins.sponsorships' => ['title' => 'Sponsorship Orders | Brotherfall Admin', 'description' => 'Manage sponsorship inquiries, payments, placement content and campaign schedules.', 'keywords' => 'sponsorship management, campaign scheduling, sponsorship orders'],
    ],
];
