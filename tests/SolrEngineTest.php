<?php

namespace Scout\Solr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
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

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('foo:1 AND bar:2')->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(0)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with(15)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('select')->with($select, null);

        $engine = $this->create_engine($client);
        $engine->search($builder);
    }

    public function test_search_sends_correct_where_in_parameters_to_solr(): void
    {
        $builder = new Builder(new SearchableModel(), '');
        $builder->where('foo', 1)
            ->whereIn('bar', [2, 3])
            ->take(20);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setCore')->with($builder->model);
        $client->shouldReceive('createSelect')
            ->andReturn($select = Mockery::mock(SelectQuery::class));

        $select->shouldReceive('setQuery')->with('foo:1 AND bar:(2 OR 3)')
            ->andReturn(Mockery::self());
        $select->shouldReceive('setStart')->with(0)->andReturn(Mockery::self());
        $select->shouldReceive('setRows')->with(20)->andReturn(Mockery::self());

        $client->shouldReceive('getEndpointFromConfig')->once();

        $client->shouldReceive('select')->with($select, null);

        $engine = $this->create_engine($client);
        $engine->search($builder);
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
    public function test_lazy_map_correctly_maps_results_to_models(): void
    {
        $data = [
            'response' => [
                'docs' => [
                    ['id' => 1, 'name' => 'John Doe'],
                ],
                'numFound' => 2,
                'maxScore' => 1.23,
            ],
            'responseHeader' => [
                'status' => 1,
                'QTime' => 3,
            ],
        ];

        $result = $this->create_result_object($data);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('createResult');

        $model = Mockery::mock(SearchableModel::class);
        $model->shouldReceive(['getKeyName' => 'id']);
        $model->shouldReceive('getScoutModelsByIds->cursor')
            ->andReturn(Collection::make([
                new SearchableModel(['id' => 1]),
            ]));
        $builder = Mockery::mock(Builder::class);

        $engine = $this->create_engine($client);
        $results = $engine->lazyMap($builder, $result, $model);

        $this->assertCount(1, $results);
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
