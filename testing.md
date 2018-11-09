
Delete any bnmng\_above\_and\_below option from the wp\_options ( delete from wp\_optons where option\_name = "bnmng\_above\_and\_below"; )

Go to the options page for Add to Posts

Checks:

	The choice for post is selected.

Choose 'none'

Click "Save Changes"

Checks

	In the database, An empty option has been created (  bnmng\_above\_and\_below | a:0:{} )

	The choice for post is highlighted again.

Add the following categories if they are not already added

	Letters
	-Consonents
	-Vowels
	Numbers
	-Integers
	--Whole Numbers
	Uncategorized

	(The dashes here denote heirarchy and are not part of the category names.)

Add the following users if they are not already added.   You don't have to use the example email addresses.  

	Alpha   alpha@bnmng.com  author
	Bravo   brave@bnmng.com  editor
	Charlie charlie@bnmng.com author
	Delta   delta@bnmng.com   editor


Click "Save Changes" again

Checks

	A new instance has been created.  Under than instance

		Delete should have two choices, "none", and "delete", and "non"e is highlighted

		Post type is "post".  This is not a form field.

		Singular View is checked

		Categories are listed in proper heirarchy

		No categories are selected

		"Any Author" is selected

Add the following to the beginning text: &ltdiv syle="color:red;"&gt;

Add the following to the end text: &lt;/div&gt;

Click "Save Changes"

Checks

	All parameters are the same as before, except the text you entered is still there

View existing posts if there are any, or add a new post

Checks

	In individual post view, 

		the text color of post content should be red

		No text outside of the post content is affected by the plugin.

	In list view

		No text should be affected by the plugin

View a page

Checks

	No page is affected by the plugin

On the settings page, uncheck "Singular View Only"

Click "Save Changes"

Checks

	All other parameters are the same.  Singular view remains unchecked

	Individual post views are the same as in the previous step

	In list view

		the text color of post content should be red

		No text outside of the post content is affected by the plugin.

	No page is affected by the plugin	

	
Add a new instance of type "Page"

Checks

	There are two instances.  

	Instance 1 is the one you created and modified earlier.  It should be as follows:

			There is a new choice under Move or Delete.  The choices are "none", "down", and "delete".  

			"none" is selected

			Post Type is Post
			
			"Singular View Only" is not checked

			No categories are selected

			"[Any Author]" is selected

			Add to Beginning is "&lt;div style="color:red"&gt;

			Add to End is &lt;/div&gt;

	Instance 2 is as follows

			The choices for Move or Delete are "none", "up", and "delete".  

			"none" is selected

			Post Type is Page
			
			"Singular View Only" is checked  (although it doesn't matter for pages)

			There is no option for categories

			"[Any Author]" is selected

			Add to Beginning and Add to End are blank

Add the following to the beginning text: &ltdiv syle="color:blue;"&gt;

Add the following to the end text: &lt;/div&gt;

Save Changes

Checks

	All content in pages are in blue.  No text outside of the page content is affected.

	All text in posts, singular or in list view, is still red

On instance 2, click "Move Up"


