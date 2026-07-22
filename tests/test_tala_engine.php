echo "<pre>";

$builder = new \Tala\Engine\ExecutionPlanBuilder();

print_r($builder->build([]));

echo "</pre>";