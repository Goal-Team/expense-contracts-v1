<form action="{{ $ESIGN_URL }}" method="post" id="formid">
    <input type="hidden" id="eSignRequest" name="eSignRequest" value="{{ $signXML }}"/>
    <input type="hidden" id="aspTxnID" name="aspTxnID" value="{{ $txn }}"/>
    <input type="hidden" id="Content-Type" name="Content-Type" value="application/xml"/>
</form>
<script>

    document.getElementById("formid").submit();
</script>