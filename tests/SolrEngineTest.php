<?php

namespace Scout\Solr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use JsonException;
use Laravel\Scout\Builder;
use Mockery;
use Orchestra\Testbench\TestCase;
use Scout\Solr\Client;
use Scout\Solr\Engines\SolrEngine;
use Scout\Solr\Tests\Fixtures\EmptySearchableModel;
use Scout\Solr\Tests\Fixtures\SearchableModel;
use Scout\Solr\Tests\Fixtures\SearchableModelWithCustomKey;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Response;
use Solarium\QueryType\Select\Query\Query as SelectQuery;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Document;
use Solarium\QueryType\Select\Result\Document as ResultDocument;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

class SolrEngineTest extends TestCase
{
    public function test_update_add_documents_to_core(): void
    {
        $model = new SearchableModel(['id' => 1]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($update = Mockery::mock(UpdateQuery::class));

        $update->shouldReceive('createDocument')->once()->andReturn(
            $document = new Document(['id' => 1])
        );
        $update->shouldReceive('addDocuments')->with([0 => $document]);
        $update->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('update')->with($update, null);

        $engine = $this->create_engine($client);
        $engine->update(Collection::make([$model]));
    }

    public function test_update_empty_searchable_array_does_not_add_documents_to_index(): void
    {
        $model = new EmptySearchableModel();

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($update = Mockery::mock(UpdateQuery::class));

        $update->shouldReceive('createDocument')->never();
        $update->shouldReceive('addDocuments')->with([]);
        $update->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('update')->with($update, null);

        $engine = $this->create_engine($client);
        $engine->update(Collection::make([$model]));
    }

    public function test_a_model_is_updated_with_a_custom_key(): void
    {
        $model = new SearchableModelWithCustomKey([
            'other_id' => 1,
        ]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($update = Mockery::mock(UpdateQuery::class));

        $update->shouldReceive('createDocument')->once()->andReturn(
            $document = new Document(['other_id' => 'custom-key.' . 1])
        );

        $update->shouldReceive('addDocuments')->with([0 => $document]);
        $update->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('update')->with($update, null);

        $engine = $this->create_engine($client);
        $engine->update(Collection::make([$model]));
    }

    public function test_a_model_is_updated_to_other_endpoint(): void
    {
        $instance = [
            'host' => 'solr2',
            'port' => 8983,
            'path' => '/',
            'core' => 'table',
        ];

        Config::set('scout-solr.endpoints.table', $instance);

        $model = new SearchableModel(['id' => 1]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($update = Mockery::mock(UpdateQuery::class));

        $update->shouldReceive('createDocument')->once()->andReturn(
            $document = new Document(['id' => 1])
        );

        $update->shouldReceive('addDocuments')->with([0 => $document]);
        $update->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once()
            ->andReturn($endpoint = Mockery::mock(Endpoint::class));

        $client->shouldReceive('update')->with($update, $endpoint);

        $engine = $this->create_engine($client);
        $engine->update(Collection::make([$model]));
    }

    public function test_delete_document_from_core(): void
    {
        $model = new SearchableModel(['id' => 1]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($delete = Mockery::mock(UpdateQuery::class));

        $delete->shouldReceive('addDeleteByIds')->with([1])->once();
        $delete->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('update')->with($delete, null);

        $engine = $this->create_engine($client);
        $engine->delete(Collection::make([$model]));
    }

    public function test_delete_model_with_a_custom_key(): void
    {
        $model = new SearchableModelWithCustomKey(['other_id' => 1]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($delete = Mockery::mock(UpdateQuery::class));

        $delete->shouldReceive('addDeleteByIds')->with(['custom-key.1'])->once();
        $delete->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('update')->with($delete, null);

        $engine = $this->create_engine($client);
        $engine->delete(Collection::make([$model]));
    }

    public function test_a_model_is_deleted_to_other_endpoint(): void
    {
        $instance = [
            'host' => 'solr2',
            'port' => 8983,
            'path' => '/',
            'core' => 'table',
        ];

        Config::set('scout-solr.endpoints.table', $instance);

        $model = new SearchableModel(['id' => 1]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($model);
        $client->shouldReceive('createUpdate')
            ->andReturn($delete = Mockery::mock(UpdateQuery::class));

        $delete->shouldReceive('addDeleteByIds')->with([1])->once();
        $delete->shouldReceive('addCommit');

        $client->shouldReceive('getEndpointFromConfig')->once()
            ->andReturn($endpoint = Mockery::mock(Endpoint::class));

        $client->shouldReceive('update')->with($delete, $endpoint);

        $engine = $this->create_engine($client);
        $engine->delete(Collection::make([$model]));
    }

    public function test_search_sends_correct_parameters_to_solr(): void
    {
        Config::set('scout-solr.select.limit', 10);
        $builder = new Builder(new SearchableModel(), '*:*');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('*:*')->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(0)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with(10)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('select')->with($select, null);

        $engine = $this->create_engine($client);
        $engine->search($builder);
    }

    public function test_search_sends_correct_parameters_to_other_solr_endpoint(): void
    {
        $instance = [
            'host' => 'solr2',
            'port' => 8983,
            'path' => '/',
            'core' => 'table',
        ];

        Config::set('scout-solr.endpoints.table', $instance);
        Config::set('scout-solr.select.limit', 10);
        $builder = new Builder(new SearchableModel(), '*:*');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('*:*')->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(0)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with(10)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once()
            ->andReturn($endpoint = Mockery::mock(Endpoint::class));

        $client->shouldReceive('select')->with($select, $endpoint);

        $engine = $this->create_engine($client);
        $engine->search($builder);
    }

    public function test_search_sends_correct_where_parameters_to_solr(): void
    {
        $builder = new Builder(new SearchableModel(), '');
        $builder->where('foo', 1)
            ->where('bar', 2)
            ->take(15);

        $select = $this->search_with_real_select($builder);

        $this->assertSame('*:*', $select->getQuery());
        $this->assertSame('foo:1 AND bar:2', $select->getFilterQuery('scout')->getQuery());
        $this->assertSame(0, $select->getStart());
        $this->assertSame(15, $select->getRows());
    }

    public function test_search_sends_correct_where_in_parameters_to_solr(): void
    {
        $builder = new Builder(new SearchableModel(), '');
        $builder->where('foo', 1)
            ->whereIn('bar', [2, 3])
            ->take(20);

        $select = $this->search_with_real_select($builder);

        $this->assertSame('*:*', $select->getQuery());
        $this->assertSame('foo:1 AND bar:(2 OR 3)', $select->getFilterQuery('scout')->getQuery());
        $this->assertSame(0, $select->getStart());
        $this->assertSame(20, $select->getRows());
    }

    public function test_search_keeps_query_text_when_filtering(): void
    {
        $builder = new Builder(new SearchableModel(), 'tolkien');
        $builder->where('genre', 'fantasy')
            ->whereIn('year', [1954, 1955]);

        $select = $this->search_with_real_select($builder);

        $this->assertSame('tolkien', $select->getQuery());
        $this->assertSame(
            'genre:fantasy AND year:(1954 OR 1955)',
            $select->getFilterQuery('scout')->getQuery()
        );
    }

    public function test_search_without_filters_adds_no_filter_query(): void
    {
        $builder = new Builder(new SearchableModel(), 'tolkien');

        $select = $this->search_with_real_select($builder);

        $this->assertSame('tolkien', $select->getQuery());
        $this->assertCount(0, $select->getFilterQueries());
    }

    public function test_default_limit_from_env_string_is_cast_to_int(): void
    {
        Config::set('scout-solr.select.limit', '50');
        $builder = new Builder(new SearchableModel(), 'tolkien');

        $select = $this->search_with_real_select($builder);

        $this->assertSame(50, $select->getRows());
    }

    public function test_empty_search_matches_all_documents(): void
    {
        $builder = new Builder(new SearchableModel(), '');

        $select = $this->search_with_real_select($builder);

        $this->assertSame('*:*', $select->getQuery());
    }

    public function test_paginate_keeps_query_text_when_filtering(): void
    {
        $builder = new Builder(new SearchableModel(), 'tolkien');
        $builder->where('genre', 'fantasy');

        $select = $this->search_with_real_select($builder, function (SolrEngine $engine, Builder $builder) {
            $engine->paginate($builder, 5, 3);
        });

        $this->assertSame('tolkien', $select->getQuery());
        $this->assertSame('genre:fantasy', $select->getFilterQuery('scout')->getQuery());
        $this->assertSame(10, $select->getStart());
        $this->assertSame(5, $select->getRows());
    }

    public function test_paginate_sends_correct_where_in_parameters_to_solr(): void
    {
        $limit = 5;
        $page = 3;

        $builder = new Builder(new SearchableModel(), '*:*');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('*:*')
            ->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(($page - 1) * $limit)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with($limit)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('select')->with($select, null);

        $engine = $this->create_engine($client);
        $engine->paginate($builder, $limit, $page);
    }

    public function test_search_sends_correct_order_by_parameters_to_solr(): void
    {
        Config::set('scout-solr.select.limit', 10);

        $builder = SearchableModel::search('*:*');
        $builder->orderBy('foo', 'desc');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('*:*')
            ->andReturn(Mockery::self());
        $select->shouldReceive('addSort')->with('foo', 'desc')->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(0)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with(10)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('select')->with($select, null);

        $engine = $this->create_engine($client);
        $engine->search($builder);
    }

    public function test_map_ids_returns_empty_collection_if_no_results(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('mapIds')->with([]);

        $engine = $this->create_engine($client);
        $results = $engine->mapIds([]);

        $this->assertCount(0, $results);
    }

    public function test_map_ids_returns_collection_if_results(): void
    {
        $doc1 = Mockery::mock(ResultDocument::class, ['id' => 1]);
        $doc2 = Mockery::mock(ResultDocument::class, ['id' => 2]);

        $docs = [
            $doc1,
            $doc2
        ];

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('mapIds')->with($docs);

        $doc1->shouldReceive('getFields')->once()->andReturn(['id' => 1]);
        $doc2->shouldReceive('getFields')->once()->andReturn(['id' => 2]);

        $engine = $this->create_engine($client);
        $results = $engine->mapIds($docs);

        $this->assertCount(2, $results);
    }

    /**
     * @throws JsonException
     */
    public function test_map_correctly_maps_results_to_models(): void
    {
        $data = [
            'response' => [
                'docs' => [
                    ['id' => 1, 'name' => 'John Doe'],
                    ['id' => 2, 'name' => 'Jane Doe'],
                ],
                'numFound' => 2,
                'maxScore' => 1.23,
            ],
            'responseHeader' => [
                'status' => 1,
                'QTime' => 3,
            ],
        ];

        $result =$this->create_result_object($data);

        $client = Mockery::mock(Client::class);

        $client->shouldReceive('createResult');

        $model = Mockery::mock(SearchableModel::class);
        $model->shouldReceive(['getKeyName' => 'id']);
        $model->shouldReceive('getScoutModelsByIds')
            ->andReturn(Collection::make([
                new SearchableModel(['id' => 1]),
                new SearchableModel(['id' => 2]),
            ]));
        $builder = Mockery::mock(Builder::class);

        $engine = $this->create_engine($client);
        $results = $engine->map($builder, $result, $model);

        $this->assertCount(2, $results);
    }

    /**
     * @throws JsonException
     */
    /**
     * @throws JsonException
     */
    public function test_map_returns_models_in_solr_order(): void
    {
        $this->create_searchable_models_table([1, 2, 3]);

        $result = $this->create_result_object($this->result_data_for_ids([3, 1]));
        $model = new SearchableModel();
        $builder = new Builder($model, '');

        $results = $this->create_engine(Mockery::mock(Client::class))->map($builder, $result, $model);

        $this->assertSame([3, 1], $results->map->getKey()->all());
    }

    /**
     * @throws JsonException
     */
    public function test_lazy_map_returns_lazy_collection_of_models_in_solr_order(): void
    {
        $this->create_searchable_models_table([1, 2, 3]);

        $result = $this->create_result_object($this->result_data_for_ids([3, 1]));
        $model = new SearchableModel();
        $builder = new Builder($model, '');

        $results = $this->create_engine(Mockery::mock(Client::class))->lazyMap($builder, $result, $model);

        $this->assertInstanceOf(LazyCollection::class, $results);
        $this->assertSame([3, 1], $results->map->getKey()->all());
    }

    /**
     * @throws JsonException
     */
    public function test_lazy_map_returns_empty_lazy_collection_if_no_results(): void
    {
        $result = $this->create_result_object($this->result_data_for_ids([]));
        $model = new SearchableModel();
        $builder = new Builder($model, '');

        $results = $this->create_engine(Mockery::mock(Client::class))->lazyMap($builder, $result, $model);

        $this->assertInstanceOf(LazyCollection::class, $results);
        $this->assertCount(0, $results);
    }

    /**
     * @throws JsonException
     */
    public function test_engine_returns_total_count_from_search_response(): void
    {
        $data = [
            'response' => [
                'docs' => [],
                'numFound' => 12,
                'maxScore' => 1.23,
            ],
            'responseHeader' => [
                'status' => 1,
                'QTime' => 3,
            ],
        ];

        $result = $this->create_result_object($data);

        $client = Mockery::mock(Client::class);

        $engine = $this->create_engine($client);
        $count = $engine->getTotalCount($result);

        $this->assertEquals(12, $count);
    }

    public function test_engine_forwards_calls_to_solr_client()
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('testMethodOnClient')->once();

        $engine = $this->create_engine($client);
        $engine->testMethodOnClient();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'scout_solr_testing');
        $app['config']->set('database.connections.scout_solr_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Runs a search against a real Solarium select query so the test can
     * assert on what would actually be sent to Solr.
     *
     * @throws JsonException
     */
    protected function search_with_real_select(Builder $builder, ?callable $run = null): SelectQuery
    {
        $select = new SelectQuery();

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')->andReturn($select);
        $client->shouldReceive('getEndpointFromConfig')->once();
        $client->shouldReceive('select')->with($select, null)
            ->andReturn($this->create_result_object($this->result_data_for_ids([])));

        $engine = $this->create_engine($client);
        $run ? $run($engine, $builder) : $engine->search($builder);

        return $select;
    }

    protected function create_searchable_models_table(array $ids): void
    {
        Schema::create('searchable_models', function (Blueprint $table) {
            $table->id();
        });

        DB::table('searchable_models')->insert(
            array_map(static fn (int $id) => ['id' => $id], $ids)
        );
    }

    protected function result_data_for_ids(array $ids): array
    {
        return [
            'response' => [
                'docs' => array_map(static fn (int $id) => ['id' => $id], $ids),
                'numFound' => count($ids),
                'maxScore' => 1.0,
            ],
            'responseHeader' => [
                'status' => 0,
                'QTime' => 1,
            ],
        ];
    }

    protected function create_engine(Client $client): SolrEngine
    {
        return new SolrEngine(
            $client,
            $this->app->make(Repository::class),
            $this->app->make(Dispatcher::class)
        );
    }

    /**
     * @throws JsonException
     */
    protected function create_result_object(array $data): Result
    {
        $query = new SelectQuery(['resultclass'=> Result::class, 'documentclass' => ResultDocument::class]);
        $query->getFacetSet();

        $response = new Response(json_encode($data, JSON_THROW_ON_ERROR), ['HTTP 1.0 200 OK']);
        return new Result($query, $response);
    }
}
