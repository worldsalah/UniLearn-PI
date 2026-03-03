<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateHtmlCssCourseCommand extends Command
{
    protected static $defaultName = 'app:create-html-css-course';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        $courseId = 52;

        // Lessons data
        $lessons = [
            // Chapter 1: Introduction (chapter_id: 150)
            [150, 'How the Web Works', 'Understanding how the internet and websites work is fundamental to web development.\n\nThe web operates on a client-server model where browsers (clients) request pages from servers.\n\nKey concepts:\n- HTTP requests and responses\n- Browser rendering process\n- Developer tools for debugging', 'Beginner', 'Understand client-server model, Learn browser rendering', 25, 25],
            
            // Chapter 2: HTML5 Fundamentals (chapter_id: 151)
            [151, 'HTML Document Structure', 'Every HTML document follows a standard structure with DOCTYPE, html, head, and body elements.\n\nKey elements:\n- DOCTYPE html declares HTML5\n- html is the root element\n- head contains metadata\n- body contains visible content', 'Beginner', 'Create HTML document structure, Understand DOCTYPE', 30, 30],
            [151, 'HTML Text Elements', 'HTML provides various elements for structuring text content including headings (h1-h6), paragraphs, lists, and text formatting tags.\n\nHeadings: h1 through h6\nParagraphs: p\nLists: ul, ol, li\nText formatting: strong, em, mark, code', 'Beginner', 'Use heading elements, Format text with semantic tags', 35, 35],
            [151, 'HTML Links and Images', 'Links and images make the web interactive and visual.\n\nAnchor tags (a) create hyperlinks\nImage tags (img) display images\nAlways include alt text for accessibility', 'Beginner', 'Create hyperlinks, Add images with proper attributes', 40, 40],
            [151, 'HTML Forms', 'Forms allow users to input and submit data.\n\nForm elements: form, input, textarea, select, button\nInput types: text, email, password, number, date, checkbox, radio\nAlways use labels for accessibility', 'Beginner', 'Create HTML forms, Use various input types', 45, 45],
            
            // Chapter 3: CSS3 Basics (chapter_id: 152)
            [152, 'Introduction to CSS', 'CSS brings your HTML to life with colors, fonts, and layouts.\n\nThree ways to add CSS:\n1. Inline CSS (style attribute)\n2. Internal CSS (style tag)\n3. External CSS (recommended - linked file)\n\nCSS syntax: selector { property: value; }', 'Beginner', 'Add CSS to HTML, Write CSS syntax', 30, 30],
            [152, 'CSS Selectors', 'Selectors target HTML elements for styling.\n\nBasic selectors: element, class, ID, universal\nCombinator selectors: descendant, child, sibling\nSpecificity determines which styles apply', 'Beginner', 'Use element, class, and ID selectors', 40, 40],
            [152, 'CSS Box Model', 'Every element is a box with content, padding, border, and margin.\n\nBox model properties:\n- width/height: content dimensions\n- padding: space inside border\n- border: edge around padding\n- margin: space outside border\n\nUse box-sizing: border-box for easier sizing', 'Beginner', 'Understand box model components', 35, 35],
            [152, 'Colors and Typography', 'Colors and fonts define your site visual identity.\n\nColor formats: named, hex, RGB, RGBA, HSL\nTypography properties: font-family, font-size, font-weight, line-height\nGoogle Fonts for web fonts', 'Beginner', 'Use various color formats, Style typography', 40, 40],
            
            // Chapter 4: CSS Layout (chapter_id: 153)
            [153, 'Display Property', 'The display property controls element behavior.\n\nDisplay values:\n- block: full width, line break\n- inline: flows with text\n- inline-block: inline but accepts dimensions\n- none: hidden', 'Intermediate', 'Understand display values', 30, 30],
            [153, 'Flexbox Layout', 'Flexbox is a powerful one-dimensional layout system.\n\nFlex container: display: flex\nProperties: flex-direction, justify-content, align-items, flex-wrap\nFlex items: flex-grow, flex-shrink, flex-basis', 'Intermediate', 'Create flex containers, Align items', 50, 50],
            [153, 'CSS Grid Layout', 'Grid is a two-dimensional layout system.\n\nGrid container: display: grid\nProperties: grid-template-columns, grid-template-rows, gap\nGrid items: grid-column, grid-row for spanning', 'Intermediate', 'Create grid layouts', 55, 55],
            [153, 'Position Property', 'Position controls element placement.\n\nPosition values:\n- static: normal flow\n- relative: offset from normal position\n- absolute: removed from flow\n- fixed: stays in place while scrolling\n- sticky: toggles between relative and fixed', 'Intermediate', 'Use position values', 45, 45],
            
            // Chapter 5: Responsive Design (chapter_id: 154)
            [154, 'Media Queries', 'Media queries apply styles based on device characteristics.\n\nSyntax: @media (max-width: 768px) { }\nCommon breakpoints: 576px (mobile), 768px (tablet), 1024px (laptop)\nMobile-first approach recommended', 'Intermediate', 'Write media queries, Define breakpoints', 40, 40],
            [154, 'Responsive Images', 'Images must adapt to different screen sizes.\n\nFluid images: max-width: 100%\nSrcset attribute for different resolutions\nPicture element for art direction', 'Intermediate', 'Create fluid images, Use srcset', 35, 35],
            [154, 'Mobile-First Design', 'Mobile-first is a design philosophy that starts with mobile layouts.\n\nBenefits:\n- Mobile traffic exceeds desktop\n- Forces focus on essential content\n- Progressive enhancement\n- Better performance', 'Intermediate', 'Apply mobile-first methodology', 45, 45],
            
            // Chapter 6: Advanced CSS (chapter_id: 155)
            [155, 'CSS Transitions and Animations', 'Bring your website to life with smooth animations.\n\nTransitions: smooth property changes\ntransition: property duration timing-function\n\nKeyframe animations:\n@keyframes name { from {} to {} }', 'Advanced', 'Create CSS transitions, Define animations', 50, 50],
            [155, 'CSS Transforms', 'Transform elements in 2D and 3D space.\n\n2D transforms: translate, rotate, scale, skew\n3D transforms: perspective, rotateX/Y/Z\ntransform-origin: center of transformation', 'Advanced', 'Apply 2D and 3D transforms', 45, 45],
            [155, 'CSS Variables', 'Create reusable values and dynamic themes.\n\nDefine: --variable-name: value\nUse: var(--variable-name)\nScope: :root for global, selector for local\nGreat for theming (light/dark mode)', 'Advanced', 'Define CSS variables, Implement theming', 40, 40],
            
            // Chapter 7: Projects (chapter_id: 156)
            [156, 'Portfolio Website Project', 'Build a professional portfolio website from scratch.\n\nSections: Hero, About, Skills, Projects, Contact\nFeatures: Responsive navigation, animations, contact form\nUse all HTML and CSS skills learned', 'Advanced', 'Plan website structure, Implement all sections', 120, 120],
            [156, 'Landing Page Project', 'Create a high-converting landing page.\n\nElements: Headline, value proposition, features, CTA\nDesign: Hero section, feature grid, testimonials\nFocus on conversion optimization', 'Advanced', 'Design landing page structure', 90, 90],
            [156, 'Certification Preparation', 'Prepare for your HTML and CSS certification exam.\n\nExam topics: HTML (40%), CSS (50%), Best Practices (10%)\nStudy tips: Review lessons, practice coding, build projects\nResources: MDN, W3C Validator, CSS-Tricks', 'Advanced', 'Review key concepts, Prepare for certification', 60, 60],
        ];

        $inserted = 0;
        foreach ($lessons as $lesson) {
            try {
                $conn->executeStatement(
                    'INSERT INTO lesson (title, content, chapter_id, sort_order, difficulty, learning_objectives, estimated_time, duration, type, is_preview, status, is_completed) VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM lesson l WHERE l.chapter_id = ?), ?, ?, ?, ?, "text", 0, "published", 0)',
                    [$lesson[1], $lesson[2], $lesson[0], $lesson[0], $lesson[3], $lesson[4], $lesson[5], $lesson[6]]
                );
                $inserted++;
                $output->writeln("Added: {$lesson[1]}");
            } catch (\Exception $e) {
                $output->writeln("Error adding {$lesson[1]}: " . $e->getMessage());
            }
        }

        $output->writeln("<info>Total lessons added: {$inserted}</info>");
        return Command::SUCCESS;
    }
}
