Working with forms
##################

Powerform functionality can simplify and automate vast portions of this work, and can also do it more securely
than most programmers would be able to do in code they wrote themselves.

Powerform handle three distinct parts of the work involved in forms:

- preparing and restructuring data to make it ready for rendering.
- creating HTML forms for the data
- receiving and processing submitted forms and data from the client.

It is possible to write code that does all of this manually, but Powerform can take care of it all for you.

Form class
----------

At the heart of this system of components is Powerforms' :ref:`Form<form_class>` class. In much the same way that a
Powerorm model describes the logical structure of an object, its behavior, and the way its parts are represented to us,
a :ref:`Form<form_class>` class describes a form and determines how it works and appears.

In a similar way that a model class's fields map to database fields, a form class's fields map to HTML form <input>
elements. (A :ref:`Model Form<model_form_class>` maps a model class's fields to HTML form <input> elements via a Form)

A form's fields are themselves classes; they manage form data and perform validation when a form is submitted.
A :ref:`DateField<form_datefield>` and a :ref:`FieldField<form_datefield>` handle very different kinds of data and have 
to do different things with it.

A form field is represented to a user in the browser as an HTML "widget" - a piece of user interface machinery.
Each field type has an appropriate default Widget class, but these can be overridden as required.