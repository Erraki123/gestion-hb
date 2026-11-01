@php $msg = $exception->getMessage() ?: 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.'; @endphp
<x-error-page code="403" title="Accès refusé" :message="$msg" />