<script type="text/javascript">
    $(document).ready(function() {
        $('#{{$id}}').dataTable({
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "{{ URL::to($ajax_source) }}"
        });
    });
</script>