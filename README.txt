23/08/2022
-------------------------------------------
Edmodo Quiz Import Block for Moodle
-------------------------------------------
This will allow you to import Edmodo quizzes into Moodle's Question Bank. It relies on the Edmodo Quizzes Export Google Chrome Extension by Bookwidgets.
That can be obtained from here: https://chrome.google.com/webstore/detail/edmodo-quizzes-export/ehfboobiajeoeniifjgkhpemkbndlfab

If you don't want to install the plugin on your own site, you can just use it from the Import Edmodo Quiz to Moodle page on the Poodll Demo Site:
https://demo.poodll.com/course/view.php?id=67

NB If your Edmodo quiz file has a lot of attached media then the processing will require a lot of memory, and you may need to adjust your Moodle site's PHP memory_limit setting. If your Edmodo quiz zip file when exported (ie before Moodle conversion) is more than 250MB, that would indicate that you have a lot of media.

-------------------------------------------
How to install on a moodle 3.x/4.x site.
-------------------------------------------
Method One
1.- Visit your Moodle site's "site administration -> plugins -> install plugins" page, 
2.- Choose "block" and drag the plugin zip folder into the "ZIP Package" area.
3.- Follow the on screen instructions to complete the installation 
NB If the "blocks" folder does not have the correct permissions you will see a warning message
and will need to change the permissions, or use Method Two

Method Two
1.- Unzip the edmodo.zip archive to your local computer.
2.- This should give you a folder named "edmodo".
3.- Upload the "edmodo" folder to your [moodle site]/blocks/ folder using FTP or CPanel.
4.- Visit your Admin/Notifications page so that the block gets installed. 
 This will not create any tables in your moodle database.

For both methods, at the end of the installation process, the plugin configuration settings will be displayed.
These are explained below. They may be completed at this point, or at any time, by visiting the plugin settings page.

--------------------------------------------------------------
Configuring block Edmodo for Moodle
--------------------------------------------------------------

Plugin Settings for Edmodo Block
***********************************************
The settings for the Edmodo block can be found at:
[Moodle Site]/Site Administration -> Plugins -> Blocks -> Edmodo

There is only one setting. To hide or show the import to question bank directly option. If it is a service for users you probably do not want to enable this, or your own question bank will get loaded up.

How to Add a Edmodo Quiz Block to a Page
***********************************************
Go into Edit mode and from the "Add a Block" block, choose to add a Edmodo block.


--------------------------------------------------------------
Using block Edmodo for Moodle
--------------------------------------------------------------

A. Export from Edmodo Quiz Export file to Moodle Quiz XML file
***********************************************

   1. From a page displaying the Edmodo block, choose "Create and download Moodle questions(XML)"

   2. You have a choice of 4 types of numbering for the exported multiple choice questions:
    * a., b., c. (the default numbering type)
    * A., B., C., D.
    * 1., 2., 3.
    * no numbering

   3. Cloze questions may be case insensitive or sensitive.

    * Case insensitive. Student responses will be accepted as correct regardless of the original term (uppercase or lowercase).
          o Example: original entry "Moodle". Accepted correct responses: "Moodle", "moodle".
    * Case sensitive. Student responses will be only be accepted as correct if the text AND case match that of the original term.
          o Example: original entry "Moodle". Accepted correct response: "Moodle".

   4. Upload/drag-drop the Edmodo Quiz Export file into the file area. This is a zip file and you can only obtain it by using the Edmodo Quizzes Export Google Chrome Extension by Bookwidgets.

   5. Press the "Create Moodle Questions" button.

   5. The questions will be created and the file will download immediately. It may take some time if you have a lot of questions.
    NB Your browser will not leave the export page at this point.

B. Export from  Edmodo Quiz Export file to Moodle Question Bank
***********************************************

 1. From a page displaying the Edmodo block, choose "Create and import to Moodle question bank"

 2. As for A) exporting to an XML file, but the final step of exporting to a file, is that the questions are automatically created in the current course's question bank.


C. Import to the Moodle question bank the from exported XML file.
************************************

   1. Turn editing on

   2. Navigate to Question Bank from a courses administration menu. It is a bit hard to find, you should find the menu that has "Edit Settings, Course Completion, GradeBook Setup .." and choose the "more" option.

   3. From the Question Bank , choose "Import"

   4. Set these settings:
      File format : Moodle XML format
      Import from file upload: Drag and drop , or choose, the xml file you exported previously

   6. If all goes well, the imported questions should get displayed on the next screen.

   5. Click Continue.

   8. On the next page, the Question bank displays the new category name 

D. Create Moodle quiz(zes) from a question bank category
***********************************************

   1. From a page displaying the Edmodo block, choose "Make quizzes from Moodle question bank"

   2. Choose the course section into which the quizzes should be created

   3. Choose the question bank category from which to make quizzes. For each category or sub-category that contains questions, a quiz will be created.

   4. Press the "Create Quizzes" button