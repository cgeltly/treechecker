<script type="text/javascript">
    $(document).ready(function() {
        $('#{{$id}}').dataTable({
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "{{ URL::to($ajax_source) }}",

            //number of records to be displayed in tables
            "iDisplayLength": 100,
            
            //which column to sort on: warning can affect subtables and family relationship graphics
            // "aaSorting": [[ 4, 'asc' ]]
        });
    });
</script>