require "json"
require "faraday"

raise "Usa 'rspec #{__FILE__}'" unless defined? RSpec

addresses = ["127.0.0.1"]
port = 8081

$random_price = rand(10..10000)
$body = { data: { type: "products", attributes: { marca: "Adidas#{$random_price}", nome: "superstar#{$random_price}", prezzo: $random_price } } }

addresses.each do |address|
  RSpec.describe "Test", :type => :request do

    describe "POST /products" do
      before (:each) do
        @response = Faraday.post("http://#{address}:#{port}/products", JSON.generate($body), { 'Content-Type' => 'application/json' })
        @json_response = JSON.parse(@response.body)
        $id = @json_response["data"]["id"]
      end

      it { expect(@response.status).to eq(201) }
      it { expect(@json_response["data"]).not_to be_an_instance_of(Array) }
      it { expect(@json_response["data"]).to be_an_instance_of(Hash) }
      it { expect(@json_response["data"]).to have_key("type") }
      it { expect(@json_response["data"]).to have_key("id") }
      it { expect(@json_response["data"]).to have_key("attributes") }

      it { expect(@json_response["data"]["attributes"]).to have_key("nome") }
      it { expect(@json_response["data"]["attributes"]).to have_key("marca") }
      it { expect(@json_response["data"]["attributes"]).to have_key("prezzo") }
      it { expect(@json_response["data"]["attributes"]["nome"]).to eq($body[:data][:attributes][:nome]) }
      it { expect(@json_response["data"]["attributes"]["marca"]).to eq($body[:data][:attributes][:marca]) }
      it { expect(@json_response["data"]["attributes"]["prezzo"]).to eq($body[:data][:attributes][:prezzo]) }

    end

    describe "GET /products" do
      before (:each) do
        @response = Faraday.get("http://#{address}:#{port}/products")
        @json_response = JSON.parse(@response.body)
      end

      it { expect(@response.status).to eq(200) }
      it { expect(@json_response["data"]).to be_an_instance_of(Array) }
      it { expect(@json_response["data"][0]).to have_key("type") }
      it { expect(@json_response["data"][0]).to have_key("id") }
      it { expect(@json_response["data"][0]).to have_key("attributes") }

      it { expect(@json_response["data"][0]["attributes"]).to have_key("nome") }
      it { expect(@json_response["data"][0]["attributes"]).to have_key("marca") }
      it { expect(@json_response["data"][0]["attributes"]).to have_key("prezzo") }

    end

    describe "GET /products/:id" do
      before (:each) do
        @response = Faraday.get("http://#{address}:#{port}/products/#{$id}")
        @json_response = JSON.parse(@response.body)
      end

      it { expect(@response.status).to eq(200) }
      it { expect(@json_response["data"]).not_to be_an_instance_of(Array) }
      it { expect(@json_response["data"]).to be_an_instance_of(Hash) }
      it { expect(@json_response["data"]).to have_key("type") }
      it { expect(@json_response["data"]).to have_key("id") }
      it { expect(@json_response["data"]).to have_key("attributes") }

      it { expect(@json_response["data"]["id"]).to eq($id) }

      it { expect(@json_response["data"]["attributes"]).to have_key("nome") }
      it { expect(@json_response["data"]["attributes"]).to have_key("marca") }
      it { expect(@json_response["data"]["attributes"]).to have_key("prezzo") }

      it { expect(@json_response["data"]["attributes"]["nome"]).to eq($body[:data][:attributes][:nome]) }
      it { expect(@json_response["data"]["attributes"]["marca"]).to eq($body[:data][:attributes][:marca]) }
      it { expect(@json_response["data"]["attributes"]["prezzo"]).to eq($body[:data][:attributes][:prezzo]) }

    end

    describe "PATCH /products/:id" do
      before (:each) do
        @patch_body = { data: { type: "products", attributes: { marca: "nuova_marca", nome: "nuovo_nome", prezzo: 5 } } }

        @response = Faraday.patch("http://#{address}:#{port}/products/#{$id}", JSON.generate(@patch_body), { 'Content-Type' => 'application/json' })
        @json_response = JSON.parse(@response.body)

      end

      it { expect(@response.status).to eq(200) }

      it { expect(@json_response["data"]).not_to be_an_instance_of(Array) }
      it { expect(@json_response["data"]).to be_an_instance_of(Hash) }
      it { expect(@json_response["data"]).to have_key("type") }
      it { expect(@json_response["data"]).to have_key("id") }
      it { expect(@json_response["data"]).to have_key("attributes") }

      it { expect(@json_response["data"]["id"]).to eq($id) }

      it { expect(@json_response["data"]["attributes"]).to have_key("nome") }
      it { expect(@json_response["data"]["attributes"]).to have_key("marca") }
      it { expect(@json_response["data"]["attributes"]).to have_key("prezzo") }

      it { expect(@json_response["data"]["attributes"]["nome"]).to eq(@patch_body[:data][:attributes][:nome]) }
      it { expect(@json_response["data"]["attributes"]["marca"]).to eq(@patch_body[:data][:attributes][:marca]) }
      it { expect(@json_response["data"]["attributes"]["prezzo"]).to eq(@patch_body[:data][:attributes][:prezzo]) }
    end

    describe "DELETE /products/:id" do
      before (:each) do
        @response = Faraday.delete("http://#{address}:#{port}/products/#{$id}")
      end

      it { expect(@response.status).to eq(204) }
      it { expect(@response.status).to eq(404) }
    end
  end
end