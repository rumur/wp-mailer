
# The Wrapper on top of [`wp_mail`](https://developer.wordpress.org/reference/functions/wp_mail/) function.  
  
## Package Installation  
```composer require rumur/wp-mailer```  
  
### How to change `From Name` and `From Email`?
```php 
<?php

use Rumur\WordPress\Mailer\Mailer;     

// This will change it for all emails that being sent via that mailer. 
Mailer::useAlwaysFromEmail('fromemail@domain.com');  
Mailer::useAlwaysFromName('More then a blog.');

// However it can be changed per email only.
Mailer::make(wp_get_current_user(), 'My Blog.', 'testemail@blog.com')->send($mailable);

```

### [Create New Email Template](#new-email)
```php
<?php  
  
namespace App\Emails;  
  
use Rumur\WordPress\Mailer\Mailable;  
use Rumur\WordPress\Mailer\WordPressMailParams;  
  
final class Invoice extends Mailable  
{  
   /**  
    * @var \Order  
    */  
    protected $order;  
    
   /**  
    * Invoice constructor. 
    * @param \Order $order  
    */  
    public function __construct(\Order $order)  
    {  
      $this->order = $order;  
    }  
    
   /**  
    * Builds an email params. 
    * 
    * @return WordPressMailParams  
    */  
    public function build(): WordPressMailParams  
    {  
      // Takes Attached files from the order and converts into relative file paths  
      $as_files = array_map(static function ($file) {  
          return get_attached_file($file);  
      }, $this->order->file_ids);  
    
      $this->setAttachments($as_files);  
    
      // Also could be attached as id or an array of ids, will transform them to files.  
      $this->addAttachment($this->order->file_ids);  
    
      // Also could be mixed  
      $this->addAttachment([  
        $file_id = 2020,  
        $file_path = WP_CONTENT_DIR . '/uploads/2020/05/lorem.png',  
      ]);  
    
      // ⚠️ OPTIONAL ⚠️  
      // The subject could be explicitly set or the Email class name will be taken as a subject.  
      $this->subject(  
         sprintf(_x('Invoice Number %d', 'Invoice Subject', 'text-domain'), $this->order->id)  
      );  
    
      return parent::build();  
    }  
    
   /**  
    * The Main message of an Email. 
    * @return string  
    */  
    public function body(): string  
    {  
      // TODO: Implement body() method.  
    }  
}
```

### [Basic Usage](#usage)
```php
<?php

use Rumur\WordPress\Mailer\Mailer;
use Rumur\WordPress\Mailer\Dispatcher;  
use Rumur\WordPress\Mailer\WordPressMailParams;

$order = OrderRepository::find(214);  
  
Mailer::instance()  
     // ⚠️ OPTIONAL ⚠️  
     // These local options will be used instead of always ones.  
     ->from('Custom From Name', 'custom@domain.com')  
    
     // If `to` gets a \WP_User instance,  
     // it also will set the locale from user's data. 
     // You also allowed to pass a regular valid email as well.  
     ->to(wp_get_current_user())  
    
     // ⚠️ OPTIONAL ⚠️  
     // Sets the Carbon Copy of the email.  
     ->cc(['carboncopy@domain.com', get_user_by('id', 2222)])  
    
     // ⚠️ OPTIONAL ⚠️  
     // Sets the Blind Carbon Copy of the email.  
     ->bcc('blindcarboncopy@domain.com')
    
     // ⚠️ OPTIONAL ⚠️  
     // Use the `locale` method in order to explicitly set the locale of the email,
     // otherwise the locale will be taken from a user if a WP_USer has been passed via a `to` method
     // ⚠️ Note once the email has been dispatched the main blog locale will be restored back
     ->locale('en_US')  
    
     // ⚠️ OPTIONAL ⚠️  
     // This listener will be triggered in case of the mailer encountered with an error  
     ->onFailure(static function (\WP_Error $error, WordPressMailParams $params, Dispatcher $dispatcher) {  
         // Do something with that error.  
     })
     
     // ⚠️ OPTIONAL ⚠️  
     ->onSuccess(static function (WordPressMailParams $params, Dispatcher $dispatcher) {  
          // Do something with that information.  
     })  
    
     // ⚠️ OPTIONAL ⚠️  
     // The Email could be sent when a WordPress action has been fired.  
     ->sendOnAction('user_payment_completed', new App\Emails\Invoice($order))  
    
     // ⚠️ OPTIONAL ⚠️  
     // The Email could be sent when the condition is `true`.  
     ->sendWhen($order->isPayed(), new App\Emails\Invoice($order))  
     
     // If sending options above did not meet your need
     // There is the main method that dispatches the email.  
     ->send(new App\Emails\Invoice($order));

```

## License
This package is licensed under the MIT License - see the [LICENSE.md](https://github.com/rumur/wp-mailer/blob/master/LICENSE) file for details.