
var last_publish = null, t, per = 5000;
function check_reload(){
    var xhr = $.ajax({
        url: 'note_update.txt',
        success: function(response) {
            if( last_publish == null ) last_publish = xhr.getResponseHeader('Last-Modified');
		
			if( last_publish != xhr.getResponseHeader('Last-Modified') ){
				console.log( 'must-reload' ); 
               $( "<div style='background-color:#FF0000; color:white'>This application has been republished on the server.  Please reload the page.</div>" ).dialog( {width:$(window).width(), title:'Please reload the page', modal:true} );
				clearTimeout( t );
            }
			else{
                console.log( 'we good' );
                t= setTimeout(check_reload, per);
            }
        }
    }); 
}
setTimeout(check_reload, per);
