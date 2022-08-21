04/10/2014 10:08:33
-------------------------------------------
How to install on a moodle 2.x site.
-------------------------------------------
Method One
1.- Visit your Moodle site's "site administration -> plugins -> install plugins" page, 
2.- Choose "block" and drag the edmodo.zip folder into the "ZIP Package" area.
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
Configuring block EdmodoQuiz for Moodle 2.x
--------------------------------------------------------------

Plugin Settings for EdmodoQuiz Block
***********************************************
The settings for the EdmodoQuiz block can be found at:
[Moodle Site]/Site Administration -> Plugins -> Blocks -> EdmodoQuiz

The most important of the settings are the 2 API keys from Edmodo.com.
You need to make these over at edmodo.com once you are logged in there. They are free.
i)  Go to https://edmodo.com/api-dashboard
ii) Create an "application", and call it "Edmodo to Moodle" (or anything you like).
iii) For the redirect URL of your site, just use the base URL of your Moodle site. eg.
http://mysite.com/moodle or http://moodle.mysite.com
iii) Copy and paste both keys into the settings pages for the block.
You can only make one "application" per edmodo account. It is only used when creating edmodo activities or import xml files.
Your students will not need to login to edmodo or be aware of any of this. You only need one set of keys per Moodle site.
You will use the same keys for the EdmodoImport module and the EdmodoQuiz block.

The final setting indicates the number of term/definition pairs that should make up a single Moodle matching question.

How to Add a Edmodo Quiz Block to a Page
***********************************************
Go into Edit mode and from the "Add a Block" block, choose to add a EdmodoQuiz block.


--------------------------------------------------------------
Using block EdmodoQuiz for Moodle 2.x
--------------------------------------------------------------

A. Export from Edmodo to Moodle quiz XML file
***********************************************

   1. From a page displaying the EdmodoQuiz block, choose "Export Questions from Edmodo."

   2. Use the search form at the top of the export page to either:
    i) display all the currently logged in user's edmodo sets
    ii) search for keywords in set titles
    iii) display all edmodo sets for a specified Edmodo userid.

   3. From the search results, select the edmodo sets to export. They can be previewed without leaving the page, by pressing the preview button.

   4. Select from any or all of the 3 question types supported, multichoice, short answer and matching.
Multiple choice questions will consist of the following elements:
    * question text = glossary entry definition
    * correct answer = glossary entry concept
    * distracters = 3 incorrect options from the same edmodo set.

You have a choice of 4 types of numbering for the exported multiple choice questions:
    * a., b., c. (the default numbering type)
    * A., B., C., D.
    * 1., 2., 3.
    * no numbering

Short answer questions require the student to enter manually the correct answer.
    * Case insensitive. Student responses will be accepted as correct regardless of the original term (uppercase or lowercase).
          o Example: original entry "Moodle". Accepted correct responses: "Moodle", "moodle".
    * Case sensitive. Student responses will be only be accepted as correct if the text AND case match that of the original term.
          o Example: original entry "Moodle". Accepted correct response: "Moodle".

Matching questions make sets of term and definition pairs. Students must select the correct term from a drop down list to match the displayed definition.
The number of term/definition pairs that make up a single matching question is determined by the matching question subquestion count setting in the Edmodo Quiz settings.
The number of matching questions generated per Edmodo set will be the number of terms in the Edmodo set divided by the matching question subquestion count.

5. When done, click on the "Export to Moodle Questions(XML)" button.

6. The file will download immediately. 
    NB Your browser will not leave the export page at this point. You can continue searching and exporting.

B. Export from Edmodo to Moodle Question Bank
***********************************************
As for A) exporting to an XML file, but the final step of exporting to a file, is that the questions are automatically created in the current course's question bank.


C. Export from Edmodo to Moodle EdmodoImport file
***********************************************
 1. From a page displaying the EdmodoQuiz block, choose "Export Activities from Edmodo."

 2. Use the search form at the top of the export page to either:
    i) display all the currently logged in user's edmodo sets
    ii) search for keywords in set titles
    iii) display all edmodo sets for a specified Edmodo userid.

3. From the search results, select the edmodo sets to export. They can be previewed without leaving the page, by pressing the preview button.

4. Select any or all of the Edmodo activity types displayed

5. When ready click the "Export to Moodle Drag and Drop" button.

6. The file will download immediately. 
    NB Your browser will not leave the export page at this point. You can continue searching and exporting.


D. Import to the quiz questions bank from exported XML file.
************************************

   1. Turn editing on

   2. In Course Administration block, expand the Question Bank item

   3. Choose "Import"

   4. Set these settings:
      File format : Moodle XML format
      General: Check get category from file (optional) 
      General: Check get context from file (optional)
      Import from file upload: Drag and drop , or choose, the xml file you exported previously

   6. If all goes well, the imported questions should get displayed on the next screen.

   5. Click Continue.

   8. On the next page, the Question bank displays the new category name 

E. Import Moodle EdmodoImport activities from the exported file.
************************************
    1. Make sure that drag and drop editing is enabled at:
    Site administration -> development -> experimental -> experimental settings -> Drag and drop upload of text/link
    
    2. Open the course page in edit mode, of the course you will add the activities to.

    3. Open the exported text file in MS Word, Notepad++ or similar text editor. 
    NB Not all editors support drag and drop. Windows notepad does not. If you experience trouble try a different editor.

    4. Each line of text in the file represents an activity. 
    Select all of the text from a single line, and drag it into the location on the course page you wish the activity to appear.

    5. Moodle will ask which kind of activity you wish to create. Select "EdmodoImport". And give your activity a nice name.
