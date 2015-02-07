<script type="text/javascript">
    $(document).ready(function() {
        $('#{{$id}}').dataTable({
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "{{ URL::to($ajax_source) }}",
            "iDisplayLength": 100,
            "aaSorting": [[ 4, 'asc' ]]
        });
    });
</script>