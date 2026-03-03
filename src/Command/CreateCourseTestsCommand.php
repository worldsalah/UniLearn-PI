<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCourseTestsCommand extends Command
{
    protected static $defaultName = 'app:create-course-tests';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        
        // Level Test Questions (test_id: 35)
        $levelQuestions = [
            ['What does HTML stand for?', '["Hyper Text Markup Language", "High Tech Modern Language", "Hyper Transfer Markup Language", "Home Tool Markup Language"]', '0', 'HTML stands for Hyper Text Markup Language, the standard markup language for creating web pages.', 1, 'Beginner'],
            ['Which tag is used for the largest heading?', '["<h6>", "<h1>", "<heading>", "<head>"]', '1', 'The h1 tag defines the largest and most important heading. Headings range from h1 (largest) to h6 (smallest).', 1, 'Beginner'],
            ['What is the correct HTML element for inserting a line break?', '["<break>", "<lb>", "<br>", "<newline>"]', '2', 'The br tag inserts a single line break. It is an empty tag which means it has no end tag.', 1, 'Beginner'],
            ['Which HTML attribute is used to define inline styles?', '["class", "styles", "style", "font"]', '2', 'The style attribute is used to add inline CSS styles directly to an HTML element.', 1, 'Beginner'],
            ['What does CSS stand for?', '["Creative Style Sheets", "Cascading Style Sheets", "Computer Style Sheets", "Colorful Style Sheets"]', '1', 'CSS stands for Cascading Style Sheets. It describes how HTML elements should be displayed.', 1, 'Beginner'],
            ['Which CSS property is used to change the text color?', '["text-color", "font-color", "color", "foreground-color"]', '2', 'The color property is used to set the color of text content.', 1, 'Beginner'],
            ['Which CSS property controls the text size?', '["font-size", "text-size", "font-style", "text-style"]', '0', 'The font-size property sets the size of the font.', 1, 'Beginner'],
            ['How do you select an element with id demo?', '[".demo", "#demo", "demo", "*demo"]', '1', 'The # symbol followed by the id name selects an element with a specific id.', 1, 'Beginner'],
            ['Which HTML element is used to define the title of a document?', '["<meta>", "<title>", "<head>", "<header>"]', '1', 'The title element is placed inside the head section and defines the document title.', 1, 'Beginner'],
            ['What is the correct HTML element for inserting an image?', '["<img>", "<image>", "<pic>", "<src>"]', '0', 'The img tag is used to embed an image. It requires the src attribute to specify the image URL.', 1, 'Beginner'],
            ['Which property is used to change the background color?', '["bgcolor", "background-color", "color", "background"]', '1', 'The background-color property sets the background color of an element.', 1, 'Beginner'],
            ['How do you make each word in a text start with a capital letter?', '["text-transform: capitalize", "text-transform: uppercase", "text-style: capitalize", "transform: capitalize"]', '0', 'text-transform: capitalize transforms the first character of each word to uppercase.', 1, 'Beginner'],
            ['Which HTML attribute specifies an alternate text for an image?', '["title", "src", "alt", "href"]', '2', 'The alt attribute provides alternative text for an image if the image cannot be displayed.', 1, 'Beginner'],
            ['What is the correct HTML for creating a hyperlink?', '["<a href=\"url\">text</a>", "<a name=\"url\">text</a>", "<link href=\"url\">text</link>", "<hyperlink>url</hyperlink>"]', '0', 'The a tag with the href attribute creates a hyperlink to another page or resource.', 1, 'Beginner'],
            ['Which CSS property is used to change the font of an element?', '["font-family", "font-style", "font-weight", "font-type"]', '0', 'The font-family property specifies the font for an element.', 1, 'Beginner'],
        ];
        
        foreach ($levelQuestions as $q) {
            $conn->executeStatement(
                'INSERT INTO course_test_question (course_test_id, question, options, correct_answer, explanation, points, difficulty) VALUES (35, ?, ?, ?, ?, ?, ?)',
                $q
            );
        }
        $output->writeln('Added 15 level test questions');
        
        // Create Certification Test
        $conn->executeStatement(
            "INSERT INTO course_test (course_id, title, description, time_limit, passing_score, test_type, created_at) VALUES (52, 'HTML & CSS Certification Exam', 'Final certification exam covering all HTML and CSS topics. Pass this exam to earn your professional certification.', 30, 70, 'certification', NOW())"
        );
        
        $certTestId = $conn->lastInsertId();
        $output->writeln("Created certification test with ID: {$certTestId}");
        
        // Certification Test Questions
        $certQuestions = [
            ['What is the purpose of the DOCTYPE declaration?', '["To define the document encoding", "To tell the browser which HTML version to use", "To add metadata", "To create a comment"]', '1', 'DOCTYPE tells the browser which HTML version the page is written in.', 2, 'Intermediate'],
            ['Which HTML5 element is used for navigation links?', '["<nav>", "<navigation>", "<navigate>", "<menu>"]', '0', 'The nav element defines a set of navigation links.', 2, 'Intermediate'],
            ['What is the semantic HTML element for independent, self-contained content?', '["<section>", "<article>", "<aside>", "<div>"]', '1', 'The article element specifies independent, self-contained content.', 2, 'Intermediate'],
            ['Which CSS property creates space between the border and content?', '["margin", "padding", "spacing", "border-spacing"]', '1', 'Padding creates space between the content and the border.', 2, 'Intermediate'],
            ['What is the default value of the position property?', '["relative", "fixed", "static", "absolute"]', '2', 'Static is the default position value for all elements.', 2, 'Intermediate'],
            ['How do you make a flex item grow to fill available space?', '["flex-grow: 1", "flex-fill: true", "flex: expand", "grow: auto"]', '0', 'flex-grow: 1 makes the item grow to fill available space.', 2, 'Intermediate'],
            ['Which CSS property is used to create a grid container?', '["display: grid", "layout: grid", "container: grid", "grid-layout: true"]', '0', 'display: grid creates a grid container.', 2, 'Intermediate'],
            ['What is the correct media query for screens smaller than 768px?', '["@media (max-width: 768px)", "@media (min-width: 768px)", "@media screen-768", "@media width < 768px"]', '0', 'max-width: 768px targets screens 768px and smaller.', 2, 'Intermediate'],
            ['How do you create a CSS variable?', '["var: name", "--name: value", "$name: value", "@var name"]', '1', 'CSS variables are created with --name: value syntax.', 2, 'Intermediate'],
            ['Which property is used to create smooth transitions?', '["animation", "transition", "transform", "effect"]', '1', 'The transition property creates smooth changes between states.', 2, 'Intermediate'],
            ['What is the correct way to apply multiple transforms?', '["transform: rotate(45) scale(1.5)", "transform: rotate(45deg) scale(1.5)", "transforms: rotate, scale", "transform-all: rotate scale"]', '1', 'Multiple transforms are space-separated: transform: rotate(45deg) scale(1.5).', 2, 'Advanced'],
            ['Which value of background-size scales the image to cover the entire container?', '["contain", "cover", "fill", "scale"]', '1', 'cover scales the image to cover the entire container, possibly cropping it.', 2, 'Intermediate'],
            ['What is the purpose of the z-index property?', '["To set element size", "To control stacking order", "To set zoom level", "To define index order"]', '1', 'z-index controls the stacking order of positioned elements.', 2, 'Intermediate'],
            ['How do you center a block element horizontally?', '["margin: auto", "align: center", "text-align: center", "margin-center: true"]', '0', 'margin: auto centers a block element horizontally.', 2, 'Intermediate'],
            ['Which input type is used for email addresses with validation?', '["text", "email", "mail", "address"]', '1', 'type="email" provides email validation.', 2, 'Intermediate'],
            ['What is the purpose of the alt attribute on images?', '["To add title", "To provide alternative text for accessibility", "To set alignment", "To define image source"]', '1', 'alt provides alternative text for screen readers and when images fail to load.', 2, 'Intermediate'],
            ['How do you select all paragraph elements inside a div?', '["div.p", "div p", "div > p", "div + p"]', '1', 'div p selects all paragraphs inside a div (descendant selector).', 2, 'Intermediate'],
            ['What is the box-sizing property used for?', '["To create boxes", "To include padding and border in width calculation", "To set box dimensions", "To create borders"]', '1', 'box-sizing: border-box includes padding and border in the element width.', 2, 'Intermediate'],
            ['Which CSS property creates rounded corners?', '["border-radius", "corner-radius", "border-corner", "round-corners"]', '0', 'border-radius creates rounded corners on elements.', 2, 'Intermediate'],
            ['What is the purpose of the viewport meta tag?', '["To set page zoom", "To control page scaling on mobile devices", "To define visible area", "To set screen size"]', '1', 'The viewport meta tag controls how the page scales on mobile devices.', 2, 'Advanced'],
        ];
        
        foreach ($certQuestions as $q) {
            $conn->executeStatement(
                'INSERT INTO course_test_question (course_test_id, question, options, correct_answer, explanation, points, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?)',
                array_merge([$certTestId], $q)
            );
        }
        $output->writeln('Added 20 certification test questions');
        
        $output->writeln('<info>Course tests created successfully!</info>');
        return Command::SUCCESS;
    }
}
