#!/bin/bash

# Sample data population script for portfolio database
# This script adds sample data for testing the frontend integration

DB_HOST="localhost"
DB_USER="portfolio_api"
DB_PASS="portfolio_2025_secure"
DB_NAME="netanel_portfolio"

echo "🗂️  Populating portfolio database with sample data..."

# Projects sample data
mysql -u $DB_USER -p$DB_PASS $DB_NAME << 'EOF'
-- Insert sample projects
INSERT INTO projects (title, description, technologies, github_url, demo_url, image_url, status, priority) VALUES
('Portfolio Website', 'A responsive portfolio website built with Flutter Web and PHP backend', '["Flutter", "PHP", "MySQL", "Docker"]', 'https://github.com/netanelklein/netanel-homepage', 'https://netanelk.com', '/uploads/portfolio-site.jpg', 'active', 10),
('E-Commerce Platform', 'Full-stack e-commerce solution with modern UI/UX', '["Vue.js", "Node.js", "MongoDB", "Stripe"]', 'https://github.com/netanelklein/ecommerce', 'https://demo-shop.netanelk.com', '/uploads/ecommerce.jpg', 'active', 9),
('Mobile Task Manager', 'Cross-platform mobile app for task management', '["Flutter", "Firebase", "Redux"]', 'https://github.com/netanelklein/task-manager', null, '/uploads/task-app.jpg', 'active', 8),
('Data Analytics Dashboard', 'Real-time analytics dashboard for business insights', '["React", "Python", "PostgreSQL", "Chart.js"]', 'https://github.com/netanelklein/analytics-dashboard', null, '/uploads/analytics.jpg', 'completed', 7);

-- Insert sample skills
INSERT INTO skills (name, category, level, description) VALUES
-- Frontend Skills
('Flutter', 'Frontend', 'expert', 'Cross-platform mobile and web development'),
('React', 'Frontend', 'advanced', 'Building interactive user interfaces'),
('Vue.js', 'Frontend', 'advanced', 'Progressive web application development'),
('TypeScript', 'Frontend', 'advanced', 'Type-safe JavaScript development'),
('HTML/CSS', 'Frontend', 'expert', 'Modern web markup and styling'),
('Sass/SCSS', 'Frontend', 'advanced', 'CSS preprocessing and organization'),

-- Backend Skills
('PHP', 'Backend', 'advanced', 'Server-side web development'),
('Node.js', 'Backend', 'advanced', 'JavaScript runtime for backend'),
('Python', 'Backend', 'intermediate', 'Data processing and API development'),
('Java', 'Backend', 'intermediate', 'Enterprise application development'),
('RESTful APIs', 'Backend', 'expert', 'API design and implementation'),

-- Database Skills
('MySQL', 'Database', 'advanced', 'Relational database design and optimization'),
('PostgreSQL', 'Database', 'intermediate', 'Advanced relational database features'),
('MongoDB', 'Database', 'intermediate', 'NoSQL document database'),
('Redis', 'Database', 'intermediate', 'In-memory data store and caching'),

-- DevOps & Tools
('Docker', 'DevOps', 'advanced', 'Containerization and deployment'),
('Git', 'DevOps', 'expert', 'Version control and collaboration'),
('Linux', 'DevOps', 'advanced', 'Server administration and scripting'),
('Oracle Cloud', 'DevOps', 'intermediate', 'Cloud infrastructure and services'),
('CI/CD', 'DevOps', 'intermediate', 'Automated testing and deployment');

-- Insert sample experience
INSERT INTO experience (company, position, description, start_date, end_date, current) VALUES
('TechCorp Solutions', 'Senior Full-Stack Developer', 'Lead development of enterprise web applications using modern tech stack. Mentored junior developers and architected scalable solutions for high-traffic applications.', '2023-01-01', null, 1),
('StartupXYZ', 'Frontend Developer', 'Developed responsive web applications using React and Vue.js. Collaborated with UX/UI designers to implement pixel-perfect designs and optimize user experience.', '2022-03-01', '2022-12-31', 0),
('FreelanceDev', 'Freelance Web Developer', 'Built custom websites and web applications for various clients. Specialized in e-commerce solutions and content management systems.', '2021-06-01', '2022-02-28', 0),
('DevAgency Pro', 'Junior Developer', 'Worked on client projects using PHP and JavaScript. Gained experience in full-stack development and agile methodologies.', '2020-09-01', '2021-05-31', 0);

-- Insert sample education
INSERT INTO education (institution, degree, field, description, start_date, end_date, gpa) VALUES
('Tel Aviv University', 'Bachelor of Science', 'Computer Science', 'Focused on software engineering, algorithms, and data structures. Completed coursework in web development, mobile programming, and database systems.', '2018-09-01', '2022-06-30', 3.80),
('TechBootcamp Pro', 'Full-Stack Web Development Certificate', 'Web Development', 'Intensive 6-month program covering modern web development technologies including React, Node.js, and databases.', '2020-01-01', '2020-06-30', null),
('Coursera', 'Mobile App Development Specialization', 'Mobile Development', 'Comprehensive course series on Flutter and mobile app development best practices.', '2021-03-01', '2021-08-31', null);

EOF

echo "✅ Sample data has been added to the database!"
echo ""
echo "📊 Database contents:"
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "
SELECT 'Projects:' as table_name, COUNT(*) as count FROM projects
UNION ALL
SELECT 'Skills:', COUNT(*) FROM skills  
UNION ALL
SELECT 'Experience:', COUNT(*) FROM experience
UNION ALL
SELECT 'Education:', COUNT(*) FROM education;"
