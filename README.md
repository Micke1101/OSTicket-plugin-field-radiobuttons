# [osTicket](https://github.com/osTicket/) - Plugin Field Radiobuttons

Enables the Radiobutton field type for osTicket forms.


## To install
- Download master [zip](https://github.com/Micke1101/OSTicket-plugin-field-radiobuttons/archive/master.zip) and extract into `/include/plugins/field-radiobuttons`
- Then Install and enable as per normal osTicket Plugins in the admin panel, "Manage => Plugins".

## To configure

Visit the Admin-panel, select Manage => Plugins, choose the `Radiobuttons` plugin. The textbox allows you to move the field categorization to different groups within the admin form field type selector. 
Note: You probably don't need to change this.

## How to add a radio button

Visit the admin panel, select Manage => Forms, choose the form you want to add a radiobutton form element to,
Enter a Label for the field you want to add, select "Radiobuttons" from the Type selector, press "Save", then use the "Config" popup to configure the plugin.
You can enter the possible options in the top "Choices" textbox, and if required you can enter the default option in the box in the middle.
If your field requires a description, you can use the Help Text textbox to describe the element.
The "Settings" tab of the config popup is the normal visibility/required options of other Fields.

## Screenshot

Admin Screen:
![example_admin](http://osticket.com/forum/uploads/FileUpload/37/4f38ac84187d787d4e1a75a9bdb2a0.png)

Once the plugin is installed and enabled, it is available from the list of available form field types.

User Screen:
![example_user](http://osticket.com/forum/uploads/FileUpload/b6/814c409e5bc5f011945efeda9723ce.png)