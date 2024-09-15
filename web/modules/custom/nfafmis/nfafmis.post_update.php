<?php

/**
 * @file
 * Post update functions run before drush config:import.
 */

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Copy values from old Sub area planted field to new decimal field.
 */
function nfafmis_post_update_sub_area_planted(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'sub_area')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      $old_value = $node->get('field_sub_area_planted')->value;
      if (!empty($old_value)) {
        $node->field_subarea_planted->value = $old_value;
        $node->setNewRevision(FALSE);
        $node->save();
      }

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Sub area planted values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Copy values from old Overall area allocated field to new decimal field.
 */
function nfafmis_post_update_offer_license_area_allocated(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'offer_license')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      $old_value = $node->get('field_overall_area_allocated')->value;
      if (!empty($old_value)) {
        $node->field_overall_area->value = $old_value;
        $node->setNewRevision(FALSE);
        $node->save();
      }

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Overall area allocated values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Copy values from old Account area fields to new decimal fields.
 */
function nfafmis_post_update_account_area_fields(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'accounts_detail')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      $old_allocated = $node->get('field_area_allocated')->value;
      if (!empty($old_allocated)) {
        $node->field_account_area_allocated->value = $old_value;
      }
      $old_planted = $node->get('field_area_planted')->value;
      if (!empty($old_planted)) {
        $node->field_account_area_planted->value = $old_value;
      }
      if (!empty($old_allocated) || !empty($old_planted)) {
        $node->setNewRevision(FALSE);
        $node->save();
      }
      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Account details area values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Rename incorrectly named CFRs.
 */
function nfafmis_post_update_b() {
  // CFR terms to be renamed. Commented names will be added by the migration.
  $cfrs = [
    //'Abera',
    58 => 'Aduku (North)',
    //'Aduku (South)',
    78 => 'Ayer (1959 eucalyptus)',
    79 => 'Ayer (Bala Road)',
    80 => 'Ayer (Lira Road)',
    82 => 'Bala (North)',
    83 => 'Bala (South)',
    88 => 'Gung-Gung',
    101 => 'Lela-Olok',
    154 => 'Kigulya Hill',
    170 => 'Nsekuro Hill',
    239 => 'Moroto',
    108 => 'Nangolibwel',
    110 => 'Nyangea-Napore',
    152 => 'Katuugo',
    //'Achomai',
    204 => 'Alungamosimosi',
    211 => 'Bugondo Hill',
    215 => 'Bululu Hill',
    235 => 'Lubani',
    242 => 'Nagongera (East)',
    243 => 'Nagongera (West)',
    248 => 'Namasiga-Kidimbuli',
    251 => 'Namazingiri',
    255 => 'Ochomil',
    258 => 'Ogata-Akimenga',
    259 => 'Ogera Hill',
    320 => 'Kuzito',
    322 => 'Kyansonzi',
    345 => 'Nakawa Forestry Research',
    348 => 'Nakiza',
    356 => 'Namakupa',
    355 => 'Namawanyi & Namananga',
    //'Nambale (Kasa South)',
    362 => 'Ngogwe (Bwema Island)',
    378 => 'Wantagalala',
    497 => 'Mafuga',
    498 => 'Mbarara',
    384 => 'Bumude-Nchwanga',
    393 => 'Kabugeza (Kasanda)',
    425 => 'North Rwenzori',
    282 => 'Bunjazi',
    311 => 'Kisasa',
    325 => 'Lajabwa',
    336 => 'Manwa (South East)',
    338 => 'Mulega',
    370 => 'Tero (East)',
    371 => 'Tero (West)',
    496 => 'Kyantuhe',
    535 => 'Lul Kayonga',
    536 => 'Lul Oming',
    537 => 'Lul Opio',
    538 => 'Mt. Kei',
    542 => 'Otzi (East)',
    543 => 'Otzi (West)',
    141 => 'Kagogo (Budongo System)',
    157 => 'Kitonya (Budongo System)',
    164 => 'Mbale (Katuugo Plantations)',
    165 => 'Mpanga (Budongo System)',
    321 => 'Kyampisi (Lakeshore)',
  ];

  // Rename the CFR terms.
  foreach ($cfrs as $tid => $name) {
    Term::load($tid)->setName($name)->save();
  }
}

/**
 * Reset the Date harvested date for sub areas that have not been harvested.
 */
function nfafmis_post_update_c(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'sub_area')
      ->condition('field_area_harvested', FALSE)
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      $node->field_date_harvested->value = 0;
      $node->setNewRevision(FALSE);
      $node->save();

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Reset date harvested of @progress of @max Sub areas.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Rebuild node access permissions.
 */
function nfafmis_post_update_006_rebuild_node_permissions(&$sandbox = NULL) {
  // Rebuild node access permissions.
  node_access_rebuild(TRUE);
}

/**
 * Populate CFR global id field in taxonomy terms.
 */
function nfafmis_post_update_cfr_global_ids(&$sandbox) {
  // CFR term id names and their global ids.
  $cfrs = [
    "Abera" => "907a4a13-aa65-4f6d-948b-78aab49e2d24",
    "Abiba" => "87e8b37b-418e-4de7-9271-dc92f4de8c79",
    "Abili" => "a26d5aee-a8f6-47a1-977f-033efc201040",
    "Aboke" => "2deb4af3-54ac-4ba0-83d4-1ac3ee3fe44c",
    "Abuje" => "ea3f8080-b049-46e8-93b3-63c6c4721fb2",
    "Abunga" => "32579895-f09e-41d5-a5df-3081b36aed1a",
    "Abuya" => "2f5b8f77-bf29-4f74-9ca2-3304ad6a8c84",
    "Acet" => "fc5fd0a6-2916-4328-8615-d9bf2740e381",
    "Achuna" => "b3db691f-d86d-41ce-b1f2-25f3816383ab",
    "Achwa River" => "5bfcdcc7-6cad-4c29-bc7e-d4f60ea12948",
    "Achwali" => "37c938c0-f695-43cf-83c3-224c95b1d290",
    "Acwao" => "262d61b8-6a7b-40e4-aa0d-65fc017a3733",
    "Adero" => "1db5f81e-e848-4cce-b7ed-0a0fc66063fa",
    "Aduku (North)" => "e7433415-9446-4cb3-913a-9c2e8c79f0f6",
    "Aduku (South)" => "4aa28bd8-d730-4f70-9d5d-8223911b5d35",
    "Agoro-Agu" => "d712b80f-1728-4b88-b8fc-11f850a13b3d",
    "Ajuka" => "aba62bec-f044-4f90-ae10-f8993c08aa4d",
    "Ajupane" => "16bb0bb9-2884-4a10-8c82-e03f5037ce9b",
    "Akileng" => "79d9f4a2-f26a-4a53-9cf9-b88e5f1ff8f6",
    "Akur" => "445f1d5b-fc6e-4939-a33d-1579c4121b4a",
    "Alerek" => "14b52a1e-4bf7-492e-83ac-bc81bee10507",
    "Alit" => "1dd44c1e-7ba6-4646-ac30-2403cbf65e5a",
    "Alito" => "a35bb8fc-e057-418c-a21d-26cd0b2fdbd6",
    "Along-Kongo" => "3c77669a-fdfa-40b3-b9e5-9adee93117cd",
    "Aloro" => "2a4d49a0-d56e-411f-9d35-9363aecc3d18",
    "Alui" => "e17e43a2-6f95-4cec-97e8-e47420c27e4b",
    "Alungamosimosi" => "66456cc8-2426-4ba8-ae28-94adc1374283",
    "Aminakulu" => "619220ea-b36f-49f9-b001-617e7a2c0bcc",
    "Aminkee" => "1b37d6f5-948b-4379-9ee3-69375ca2b54a",
    "Aminteng" => "d335ad0b-242f-4b26-a7a6-1c87639f98ad",
    "Amuka" => "5e4eb262-40ac-4235-8596-813fe248d3e4",
    "Aneneng" => "5aef9001-71ed-4246-8501-6beb39c13e9f",
    "Angutewere" => "64621d60-6cf1-44af-9f0b-2900190b769a",
    "Anyara" => "75cb384b-fd01-4ae7-88af-1c809a4f9f54",
    "Apac" => "96fef6e9-77c2-4e2d-b15f-1f4dba26e7ff",
    "Apworocero" => "42b1e899-d1ed-451c-8153-46d893411adb",
    "Aram" => "7413fd2d-e7b0-4e7d-ad2b-550d68050e98",
    "Aringa River" => "01978d58-ab41-4e64-9f53-2f47684c8ab7",
    "Arua" => "952296d0-7d42-4f7b-8fe4-a83b54ded545",
    "Arweny" => "928a3319-52c0-475a-b2c6-81f07709a2d7",
    "Atigo" => "22eacf54-14b4-47fe-a32b-c0eab63b2b78",
    "Ating" => "1d87d89f-1652-4476-a21c-f61b68cbec0a",
    "Atiya" => "ad90409c-838a-4226-ae9b-699bf15a6347",
    "Atungulo" => "03378ac9-c7bf-47e1-ad54-d7197540eec6",
    "Ave" => "ee3055b2-b1ed-4206-800c-b361d9e33ca3",
    "Awang" => "53f199f0-f58a-4a1f-ae60-60938176e784",
    "Awer" => "105cfd55-088c-48ab-90fa-093adfda4d7c",
    "Ayami" => "dd51b3af-81f2-446b-b6f3-c5f3799d4634",
    "Ayer (1959 eucalyptus)" => "8158e734-8aa1-4703-a2fd-ea7c64603d63",
    "Ayer (Bala Road)" => "0b941c1f-662f-4a3c-b238-b1ccf6073001",
    "Ayer (Lira Road)" => "394d4fac-3ef0-4306-8fd5-00aeccef8699",
    "Ayipe" => "9ff03400-8b5a-440b-94c0-805d84bab732",
    "Ayito" => "ed6b8d2a-9b48-4c62-a7e3-178b6183e90e",
    "Bajo" => "e94bc042-85c1-49df-a48d-a6e033bc2c33",
    "Bala (North)" => "dc5e67b2-564b-443e-adee-43a8d73bee7e",
    "Bala (South)" => "9e3bf046-bbc3-4885-aa49-32e2d2b99242",
    "Banda Nursery" => "452c7999-8b82-4c86-ae40-59dcc0d62433",
    "Banga" => "0d364234-b04b-4882-9477-73085245afb8",
    "Barituku" => "aea347a9-276f-4edb-b5d7-9bb9a5c76fd3",
    "Bbira" => "d237d655-bdd6-4ab6-b1cc-1c432cc6f979",
    "Bikira" => "6d62afc7-d468-45e5-a278-ef6dbbf38fc6",
    "Bobi" => "9e835981-9376-419e-a203-43a4ffc4c77b",
    "Budongo" => "cc85fd3d-8376-4b74-bc9c-19b3a02a0a5f",
    "Budunda" => "604e0d63-9002-4b1a-a152-0ebc1e5ecb53",
    "Bufumira" => "607bad38-5f87-4e2c-947c-b18df01569aa",
    "Buga" => "766c54c6-ebca-4176-8330-d254fb3e7654",
    "Bugaali" => "bf3fcbc3-4916-4e28-a22c-195f9412d9ca",
    "Bugamba" => "c10667e7-74e3-41f2-ad57-02d7be4a07fc",
    "Bugana" => "262d5a76-da04-4ec7-8cdd-7084cc9f57ce",
    "Bugiri" => "f4078a2e-eaf7-4179-9c38-5030a40d94b3",
    "Bugoma" => "fdc98307-5d03-4edb-af6a-3e15a79e5bb5",
    "Bugomba" => "a47b5587-4ff3-48d2-bbf2-01647cfe5224",
    "Bugondo Hill" => "482a14e1-d997-4768-a11c-05123ecce8e6",
    "Bugonzi" => "25e1a0fc-ff2c-40f7-b2e9-05d614d43578",
    "Bugusa" => "c8392c8c-114b-4fc8-b9ad-db0c45931e7d",
    "Buhungiro" => "cfada954-f872-4167-bab6-cbc772cd23a9",
    "Bujawe" => "6c4fec15-bd0d-45b8-bc0e-155fd7875e7d",
    "Buka" => "628f9b36-3493-4585-9822-dd77b59c3971",
    "Bukaibale" => "4e80fbdb-6362-424f-81e9-741f4954095d",
    "Bukakata" => "a9b206b3-df57-46de-981c-0cc34d21f645",
    "Bukaleba" => "97e58d26-b582-4e8e-ba48-04ff609e34c6",
    "Bukedea" => "89c58de2-3e90-492e-aeeb-cb409abebd2f",
    "Bukone" => "f91b18ff-9184-4688-b20c-17488955a874",
    "Bulijjo" => "f517ab31-c47c-4ce9-adc4-1edb43ffc06a",
    "Buloba" => "fd0cc562-8490-4f0f-9704-975e174543d0",
    "Bulogo" => "ce3bdfee-d945-4236-8a54-22033c29df3c",
    "Bulondo" => "a21c5a45-deb2-4280-9a2d-e2c9ed74a71a",
    "Buluku" => "fa9daf99-b84f-44b4-be41-896e3b814186",
    "Bululu Hill" => "2ce6b77a-38ed-4240-85b7-4a8ca304e7b5",
    "Bumude-Nchwanga" => "1ba4e912-98b7-45d7-b774-7259e59f9074",
    "Bundikeki" => "2ed0e18d-35a2-4998-874a-f53f2ee94572",
    "Bunjazi" => "4b8b5114-984d-462d-8cd5-e3e5eb8ee266",
    "Busembatya" => "b56efe3f-a75a-4fa6-92c9-a05279327594",
    "Busowe" => "7fe7641f-8888-4a64-a18a-0eb90a057e6e",
    "Butamira" => "47d34ddd-2d60-49c0-8e3f-43dc0be213e7",
    "Buturume" => "43f199be-a327-4f10-a591-1a61729c5d85",
    "Buvuma" => "1f4e775a-e558-4bf4-91c7-05fcb7af9469",
    "Buwa" => "2462460f-00d4-4ff3-af38-e15c92b98c29",
    "Buwaiswa" => "401f0c50-e4a3-45b6-a55b-9b6ff6e4efa0",
    "Buwanzi" => "b2bbbde9-fbb7-484e-a977-9c7edc3d9022",
    "Buyaga Dam" => "9d68ff72-56ab-4dd1-b478-d6f4a90fa7a5",
    "Buyenvu" => "2b600972-eeb8-45bb-b2f1-99f500841b11",
    "Buziga" => "1232bc37-747e-4d3c-a890-ef0298ebfa1e",
    "Bwambara" => "280d9ef2-eb1f-495e-bb27-5bcaacc660c3",
    "Bwezigolo-Gunga" => "f8af0ba5-8e3c-4efe-92fc-927d5135e50d",
    "Degeya" => "fde974b6-b7e9-49ba-8cbe-965c1e3a9a3e",
    "East Uru" => "2f542d90-308f-4a9f-8160-500f6828ec82",
    "Echuya" => "66cf5c26-e2b1-4330-b1a7-7175158c63bf",
    "Enjeva" => "43c361f2-9e5e-48f4-a79a-555e3e6c14f9",
    "Enyau" => "3dd8d35e-1e84-40ac-b2be-f60cdf86878b",
    "Epor" => "7efda596-e2b6-416a-9f41-d7cd9fe68870",
    "Era" => "1243eb51-ba09-4481-9f5a-caebd9efbafd",
    "Eria" => "3d748009-f033-428e-834e-fbb939f1b66f",
    "Fort Portal" => "fe4dc145-be40-4a0f-a5cb-4775f651b1b7",
    "Fumbya" => "87f76c13-14fd-45a9-a1c4-4a2d4974aa81",
    "Funve" => "7d4ad14d-6f7c-42d8-942b-3ac4dfbaaaa4",
    "Gala" => "452a7842-0507-40a7-b47d-1ba80d7e2422",
    "Gangu" => "d321a24d-3f4d-452e-b0e0-a921abf6c60b",
    "Got-Gweno" => "4b6accc1-269f-4b21-b726-4327ee310766",
    "Goyera" => "37438516-0f15-4daa-bbd4-da9c530eb8a1",
    "Gulu" => "418d7689-98b3-40c0-a7ad-a608192dfac2",
    "Gung-Gung" => "be93cd64-6481-438d-ba82-8559f719f66b",
    "Guramwa" => "3c3ca355-1fb3-4c71-b28c-9896a444c1e6",
    "Gwengdiya" => "56563453-12e6-402c-a3d5-027a91ffe2c5",
    "Gweri" => "0c2dfe80-d292-44b3-91f0-99d05e5db520",
    "Ibamba" => "570008b9-6cde-4156-8350-dc98a15fcdc0",
    "Ibambaro" => "af69b7a4-e0a8-4863-a65f-ff93e274210e",
    "Igwe" => "69dcf03d-87d7-4cfa-a1d0-1d426774bb10",
    "Ihimbo" => "d2f88099-72f9-42d6-9a3d-ce5978d157ff",
    "Ilera" => "f604e788-3e5c-4530-a149-34e92878d003",
    "Irimbi" => "c2e5e599-8b64-41a0-bfe9-ffcf3249d2b0",
    "Itwara" => "ceb006a9-1f7a-4569-9aa5-978bea0d3ee2",
    "Izinga Island" => "a8ae6b6a-4123-4e84-ac8f-0dcbffdc341a",
    "Iziru" => "a8daa692-34fe-4a8d-8313-e45b0b4027eb",
    "Jubiya" => "558e55a2-1f4a-4376-9796-b907f786a0e3",
    "Jumbi" => "52e759db-d2cf-4f70-ae9c-d139d2c7c2e3",
    "Kabale" => "03af2618-d6b0-41a9-b959-bc02d874dd15",
    "Kabango-Muntandi" => "fb436a7c-5b80-45bb-8203-540696598e91",
    "Kabindo" => "7a9d4f63-9b54-46d6-8da2-5529a67271ed",
    "Kabira" => "6e3986bd-3915-40bd-be80-1af6d8dde643",
    "Kabugeza (Kasanda)" => "03b7447d-e95a-4537-a8b0-0ec108a23f24",
    "Kabukira" => "61999d2e-a910-48c4-99a5-99b4170f33c3",
    "Kabulego" => "38b110dd-23b9-44cc-bcbe-d79ae0be636f",
    "Kabuye" => "e1685ac1-305a-4f35-a477-e6525571f011",
    "Kabwika-Mujwalanganda" => "d9c7b49a-6663-4d6c-b2e0-d1769208b2c4",
    "Kachogogweno" => "61ee497f-d182-4185-bea6-38451a57b6bb",
    "Kachung" => "f3727455-1504-479c-8e42-aab2554bb5ae",
    "Kadam" => "a78f6945-55bf-45e7-9f92-c29d0a6122e4",
    "Kadre" => "549712f7-1aed-48c8-91f8-326b5b1c9cf4",
    "Kaduku" => "e57d3b9d-35ff-4784-99f4-5eec268e5fe8",
    "Kafu" => "748b1b8a-2f69-41c0-9622-0acd5d2cf461",
    "Kafumbi" => "264a22b1-dde9-450f-8061-755a475da3cb",
    "Kagadi" => "b35e9088-46b5-4534-8657-f2de858a07f7",
    "Kagogo" => "a9954c69-ed8c-44b7-b95f-1b63090c94c3",
    "Kagoma" => "96a9a6c8-c10f-4527-ac25-59a36c3be568",
    "Kagombe" => "f08db1ef-508e-4679-8ca9-fee3f3460232",
    "Kagongo" => "f331d883-a674-44e7-aaa7-763a2d92085b",
    "Kagorra" => "ff514840-be22-4d46-905d-5bf82132b6b8",
    "Kagwara" => "43a61055-5670-41e2-a3ff-40bfda271fdc",
    "Kahurukobwire" => "90ab4a43-bca0-4e65-9a6f-db2785bdbf75",
    "Kaiso" => "bd9b31f3-ed5f-404a-9124-019e09c8d713",
    "Kajansi" => "9bc49081-d8d9-4603-bd1c-1b65015cfc1d",
    "Kajonde" => "71f8d99b-2fc6-47ae-bf9b-dd978bb5d9f1",
    "Kakasi" => "47f1fada-2cab-4c7c-a48e-0f18eba8b790",
    "Kakonwa" => "ed0ec016-63be-40a4-a647-4e24d71c22ef",
    "Kalagala Falls" => "873661de-be8d-4d2d-be13-b9944add6cd0",
    "Kalandazi" => "0d514dbb-c164-4570-b2fb-d0fbbcf26d90",
    "Kalangalo" => "b93727dd-4a51-447b-a7f7-a2197c98bd7f",
    "Kalinzu" => "c6daf2e5-25f2-4433-8e9b-5ecd640e18d2",
    "Kaliro" => "37eb91bb-f44b-414f-a134-930064a39006",
    "Kalombi" => "3cb455d2-27d0-4813-8f57-4c4c52715a5b",
    "Kamera" => "3710bf4c-38cf-415f-8a58-ce4be1002439",
    "Kampala" => "51df5ee1-b824-41df-83f1-44e6e3fc2c39",
    "Kamukulu" => "ef9a5659-5996-445a-b1a6-432a22b640d6",
    "Kamusenene" => "8bf5db03-8663-4c30-ba5f-dd677e396890",
    "Kanaga" => "100035cb-daf0-46f3-910f-7b1f0afba27c",
    "Kanangalo" => "18878da6-1796-4786-b6e6-58fb351fefc8",
    "Kandanda-Ngobya" => "7218a508-d296-46b3-ad7b-79d5f67269b3",
    "Kande" => "228a0877-e97f-40be-8c20-a6a9d9515b1b",
    "Kaniabizo" => "451500c6-c4db-45c3-99af-5e5b43e29b27",
    "Kanjaza" => "65546296-831e-42c7-a1f0-0de1ac3d1e98",
    "Kano" => "7ff0e09d-03c2-48ed-925f-e48244476359",
    "Kapchorwa" => "2f92f553-81e2-4370-b477-180618daf312",
    "Kapimpini" => "e0b33853-85a4-4e51-8730-067ceff74594",
    "Kasa" => "d2968eff-f4c6-469a-b907-15c114e6b269",
    "Kasagala" => "16b42817-9025-4d0f-9b76-c619ef9f9964",
    "Kasala" => "0e62c1f2-bf0f-4696-90c8-38916a468027",
    "Kasana-Kasambya" => "47be8bf0-8953-4d9a-b613-cc7de7315838",
    "Kasato" => "906c03a7-7099-484e-9448-6bd871ce885b",
    "Kasega" => "4bf8b935-0ed7-4d86-8fd8-a72a773c10ec",
    "Kasenyi" => "6acd0bb2-e1b5-4ece-ad7c-e0b1ef2c4905",
    "Kasokwa" => "120aaa29-7101-4c00-8c2b-5a31b3622d63",
    "Kasolo" => "a09bf910-cb82-49ee-a62b-66733f296b0c",
    "Kasongoire" => "62277003-b067-4c42-a524-f2eaecc0e65c",
    "Kasonke" => "35b9156e-f698-4013-8da9-7e2008846bfb",
    "Kasozi" => "c83a0efd-f3c2-4854-bb1a-f1b3de0c2932",
    "Kasyoha-Kitomi" => "38aa8798-2bb2-44d3-a4d7-237fad67d889",
    "Katabalalu" => "be1bb6db-4abc-4d63-8055-5c9c407ed556",
    "Kateta" => "b0784643-3e6f-4625-9dee-1de8d5f8eb52",
    "Katuugo" => "1903d0c4-8f0d-45be-9d16-9c5d86640775",
    "Kavunda" => "72278462-7b6a-4bd2-b76d-c711825093a5",
    "Kaweri" => "45937e01-2a56-4197-8f69-4cd6bce23581",
    "Kazooba" => "d0c5bad3-78f6-4244-935e-df420c2f210b",
    "Keyo" => "fbcc3415-46d0-47f2-bfa2-9e0c64a0aec3",
    "Kibego" => "f8fbd20b-ce6c-4139-9781-0619ea1c58fb",
    "Kibeka" => "72103574-16ad-47bb-8217-7f923c1ce474",
    "Kifu" => "22fc4de0-8a37-491a-899f-175e6d09388c",
    "Kifunvwe" => "0e2d0755-7972-4491-90da-14ba47339241",
    "Kigona" => "33eb7ca8-11cc-49f3-b758-09f92b73e02b",
    "Kigona River" => "4783d7d6-557d-4dfc-bc39-021f9237a2d0",
    "Kigulya Hill" => "ca4f4a76-30e3-4f6d-8293-ea0ababfdf2a",
    "Kihaimira" => "a097ba31-f49c-4717-b761-c5f7a79877d2",
    "Kijanebalola" => "c7190a76-6ffd-4c4c-88a7-948b08b3760f",
    "Kijogolo" => "7140c728-1cdc-4e76-bdd3-084c394f3679",
    "Kijuna" => "d6cf0460-8a21-4dca-8034-42c60f763af6",
    "Kijwiga" => "e13d6edf-14a0-4233-92b6-cc5cd2266550",
    "Kikonda" => "bbd9c005-53f1-4abf-9399-b0a7a12ccc9b",
    "Kikumiro" => "c0bf5ce0-028c-49be-8ab1-73e7e534822e",
    "Kilak" => "bb1da7c5-1152-494f-8ecc-ce7f0cd47980",
    "Kimaka" => "dddcfe10-de12-47b4-ae9d-78fcfe18a33e",
    "Kinyo" => "72ad45f9-39a4-4324-8141-e0a317a8707a",
    "Kisakombe" => "56de3f53-8c67-44f2-9898-a53c912fcff4",
    "Kisangi" => "cd8b62ab-42e5-4c7c-bee0-03793e540204",
    "Kisasa" => "c903f72e-f918-456e-91b1-28f672ae52ed",
    "Kisisita (with Lubanga and Wangege)" => "6bbbf364-ccee-48bb-803d-f6acddf703da",
    "Kisombwa" => "07e51fef-42ff-408b-a230-cc9f660ce545",
    "Kisubi FR" => "12ffd1b5-e249-46fb-8ee6-1ceaa303c3d3",
    "Kitasi" => "7fcf2f63-2405-4f94-b718-4a48b538c622",
    "Kitechura" => "23c13d41-1a3a-464e-a87a-b5768d2293a7",
    "Kitemu" => "446ba1bb-e747-49f8-b49c-4548b26d2aff",
    "Kitonya" => "c4dfe576-20dd-45db-b354-83c64d379c28",
    "Kitonya Hill" => "8c219595-2049-4ac0-a5b3-0f9f5ca2acae",
    "Kitubulu" => "9a402b03-094d-4cb3-9bbd-2dd1af855afd",
    "Kiula" => "8b63963d-db2a-4a1a-a287-33312ad6b203",
    "Kizinkuba" => "43b06e90-768a-44b1-8d0d-c84fb279995d",
    "Koja" => "d2be6d53-5867-4171-acef-99d957bc0b67",
    "Koko" => "412cde24-c361-4424-b637-03e3f7abd1b1",
    "Kubanda" => "01492311-1ea4-43d1-ad5e-2c6167f363f9",
    "Kulo-Obia" => "1d10210b-8dcc-4599-8526-83340d6dabad",
    "Kulua" => "a03a3598-add0-4abd-900a-66b8d5c89e93",
    "Kumbu (North)" => "dd965612-a26f-4674-98cd-fc17e1947e69",
    "Kumbu (South)" => "cb29018c-0f0d-4fce-8f65-e048f321ade4",
    "Kumi" => "be01d6d1-448d-47bd-92c6-1b328aa1430d",
    "Kuzito" => "ce1142f1-a5d3-4710-a013-3dbd0f90a468",
    "Kyabona" => "baa1e78e-26f4-41fc-8744-e055c043262c",
    "Kyahaiguru" => "5444dbb0-04e6-460e-a044-9ceb6ad4c6e5",
    "Kyahi" => "eb2cb315-0f70-422f-9965-7a8fb9b9d2a6",
    "Kyalubanga" => "7390b8c1-bff0-42bc-8d09-272569f60c18",
    "Kyalwamuka" => "0cdff3b4-e1a9-4ad2-b832-42b1b1a8484a",
    "Kyamazzi" => "19e23f5f-3d0e-4a4e-962c-a96c8c3b2345",
    "Kyampisi" => "b053ef71-8362-44de-8ada-578bd3973b23",
    "Kyamugongo" => "59b01d62-5270-41e4-bc24-5d445b3838c8",
    "Kyamurangi" => "20f77eea-b12b-41b5-bf1e-6005e211359c",
    "Kyansonzi" => "b103be16-9991-4a48-81c5-f4eef985d9db",
    "Kyantuhe" => "0e278045-8629-435a-afff-f8a2c8066d2c",
    "Kyehara" => "cee17acc-ba33-4b9b-a26c-e4ea55979bcf",
    "Kyewaga" => "6e5a6c6d-d35b-446c-bb6c-a581c0f40eb1",
    "Kyirira" => "da6fd142-2633-4f6f-adf6-58cf59c4a298",
    "Labala" => "ce34f59a-65aa-4d81-a3a0-96de6849f762",
    "Lagute" => "ba8a3fc3-b350-447c-a709-ec7e72a2641d",
    "Lajabwa" => "4faa5222-d049-4135-b38a-3734c1f4a8c2",
    "Lalak" => "e0752584-32d1-4c95-9fdc-88724f02db7f",
    "Lamwo" => "195fc80d-1b71-4318-8d65-2a2a3d1e50f3",
    "Laura" => "549c7373-66a1-4204-b078-8709d014d9e7",
    "Lela-Olok" => "f0ee2117-6a56-4dd4-90dc-4b56b8b7115b",
    "Lemutome" => "145084d8-fc43-41f4-86fa-2aceb1cb000f",
    "Lendu" => "998027fe-433a-497a-b68c-6527b97529a3",
    "Linga" => "0e816a01-9e1c-4e29-9637-79cfeff037bd",
    "Lira" => "2007990f-ced1-4b91-9a81-33e6c3c3b5cc",
    "Liru" => "bef11ea7-900b-4249-ad06-7c78eb742973",
    "Lobajo" => "479b6fd4-5ecd-4b6f-a8dc-938825630fc5",
    "Lodonga" => "f41be0e1-24ba-4fb8-ac0e-25cd49d0c056",
    "Lokiragodo" => "f72da9f2-2025-42b5-b1c1-31d4c9517ed8",
    "Lokung" => "2acc6158-1ca4-4704-822b-c3c64aff6c87",
    "Lomej" => "dc193fd7-a566-45b7-8971-1645524d2e1c",
    "Lotim-Puta" => "7b5c5d7f-b990-499e-b6ee-a120b95aa37a",
    "Lubani" => "2c54fa44-bfbe-4a1d-bcd3-3181cee59f7b",
    "Lufuka" => "4fdf7f66-5d87-48c1-a57d-868656a41c0f",
    "Lukale" => "1a93fc5e-1d51-44ab-b4e5-bbef3da78c13",
    "Lukalu" => "0419b6fc-8cc9-410c-b49c-257df5be3121",
    "Lukodi" => "b5814664-c1e9-4ae1-93a8-d0146ecff9d0",
    "Lukolo" => "9023c7c5-b8d0-4017-9e0a-7860be049028",
    "Luku" => "c4a951f2-8a7b-4899-92dc-231a938f1aa0",
    "Lukuga" => "3a00d445-3bd9-411a-b30a-a911195fa294",
    "Lul Kayonga" => "ba5ae7a1-7349-43ee-a247-674289af9250",
    "Lul Oming" => "980dc300-861d-4559-bdb8-b90898bfb954",
    "Lul Opio" => "29075884-07a9-499d-9a81-f8f7f6d0dabd",
    "Luleka" => "d509bb12-5723-410c-9331-8153824a3d16",
    "Lusiba" => "2b010b15-9ad5-4627-8a3a-e52a332fa301",
    "Lutoboka" => "f7b3bde0-13b7-4c29-a798-f0407fe080b8",
    "Luvunya" => "1e501178-c572-444e-8873-bfdad622333f",
    "Luwafu" => "5ba57844-885e-4bcd-970d-fb48e03b6114",
    "Luwawa" => "5f04720a-227f-4bde-be75-d6c0f655a274",
    "Luwunga" => "ab8091bf-57a0-4da7-9333-1594fca668d4",
    "Luwungulu" => "db9ca625-4f14-46ac-9a2f-5437d240616e",
    "Lwala" => "bdc47ec8-da1a-4fe8-bdcc-718757adf967",
    "Lwamunda" => "35e22529-1838-4edc-986a-14bd9f1b26c2",
    "Mabira" => "103052cd-6790-470d-8052-842565e9bacc",
    "Mafuga" => "59f8f72b-fb2f-4241-b4b5-3b114b0b65e7",
    "Mako" => "2fb1f057-ef9e-4b8f-80eb-e200d8ee4b0a",
    "Makoko" => "86924360-7a33-49a3-a915-134bf88abd7f",
    "Makokolero" => "8844fd69-491e-4121-9e7e-eed0d99302ab",
    "Mala Island" => "8b995052-51d0-419b-8197-f029a1d24c7e",
    "Malabigambo" => "dff8100c-19bd-4377-b0f1-6dda2514b750",
    "Manwa (South East)" => "2cc4aa1e-47d4-4603-92a1-197bb3ee98ff",
    "Maruzi" => "8cb23d11-496c-4bbf-aaa2-83f79f5f64bf",
    "Masege" => "03244f83-496b-4bd2-be29-5466278b5a93",
    "Masindi" => "b7599e0c-bbfe-4053-bd30-bcba737c33ee",
    "Mataa" => "abbe4609-547b-4bf6-b507-e28f49b6d86c",
    "Matidi" => "5e4c4625-6323-4dd4-b417-b07664a06c10",
    "Matiri" => "66fd8c2f-2f3d-4c9d-8e05-34d2e2d27fd9",
    "Mbale" => "1b852fd2-6851-437b-9f34-3ffbe3c89712",
    "Mbarara" => "42695287-e420-44f7-8695-1c349d5f794e",
    "Mburamaizi" => "ec568a18-6120-4d6c-8ea8-30b12c1e7603",
    "Monikakinei" => "12962c9d-84f2-4065-98bd-f1f091afeaae",
    "Morongole" => "5f6a1a9d-55d4-461b-acc1-794f24ddbffc",
    "Moroto" => "a5f3240d-4fc4-42d3-a7e7-00aa6cadb43d",
    "Mpanga" => "3d6b1790-9c9f-4fb8-93b5-69066255d112",
    "Mpinve" => "f9203d62-7094-472e-a17f-99f8a7e4b0a9",
    "Mt. Kei" => "1671db0f-bd2f-4306-9f83-a8a39fb0ba49",
    "Mubuku" => "614d4ce9-98e2-4bb6-b1b8-2d44cdc3ad9d",
    "Mugomba" => "68d34795-3da8-466e-a0b9-6a0010ba1967",
    "Mugoya" => "3a5c8369-d38e-4094-b2b4-c48104f089d1",
    "Muhangi" => "45a5afee-809d-49e2-86ce-7fe1a5690fbf",
    "Muhunga" => "c3ff4ee9-7425-422e-861a-c4a666fe8877",
    "Muinaina" => "628ae121-8d5e-4113-8bae-2710a5b359e5",
    "Mujuzi" => "40d02d67-e230-46b3-9a0c-789c68ffd680",
    "Mukambwe" => "9d5d8bd7-c20e-4e97-9aa1-4b76029bc0a4",
    "Mukihani" => "6ad41c5a-e4cf-4981-beb6-87276c34bb68",
    "Muko" => "1b60439e-c91c-418c-b7f0-31790e12fb12",
    "Mulenga" => "b854ba6b-a55c-4a32-8603-9fa2207924a4",
    "Mulundu" => "1d183714-ff9a-49c2-b8bd-129a313e49d6",
    "Musamya" => "91fcb78b-7bec-4dec-9bf2-36b251def092",
    "Musoma" => "95270601-95f6-4a96-b046-50d3c9b588c3",
    "Mutai" => "574b82c9-ba18-44cf-adb6-219c6203b074",
    "Mwiri" => "2a40aba6-bca5-48a8-944b-902654cea0b3",
    "Mwola" => "8ce39313-5c54-4a22-97de-8cf5908f9300",
    "Nabanga" => "358fcc7d-d369-49a4-adf6-58e8b95043df",
    "Nabukonge" => "fd912ef4-6869-4585-a694-5002e0739e4f",
    "Nadagi" => "7edab5d9-55dd-4e02-9c11-722ac37bd81a",
    "Nagongera (East)" => "fcdc8708-1bba-4607-a425-20a6b40bc9d1",
    "Nakaga" => "4d0359b2-a0ec-492e-b78a-dbcbe2fe58e8",
    "Nakalanga" => "9563678b-87fb-47f2-9a44-d1938527dc0c",
    "Nakalere" => "caecefb8-720f-4289-84d2-9b97d1c06214",
    "Nakawa Forestry Research" => "4e9215f4-27f9-4786-a573-e5d7f0a1a11a",
    "Nakaziba" => "abef2d32-4718-44b2-a10c-3637313a9f08",
    "Nakindiba" => "6dcb2a4c-30fc-43b2-a54d-078328727902",
    "Nakitondo" => "f1ca82ae-9330-496c-81f0-d906ee53bb93",
    "Nakiza" => "fab0d850-33a6-48ca-be0d-93a057ead5a1",
    "Nakunyi" => "56bbeb4f-d37f-4160-b5da-16da5979c1e1",
    "Nakuyazo" => "3be7c123-602d-44d6-be9e-78864ccf8578",
    "Nakwaya" => "e275116f-bcc8-4637-83a8-d7bebcce1409",
    "Nakwiga" => "b0b638d9-ca8a-40b0-b4e6-a58c51049dae",
    "Nalubaga" => "a308f538-002f-4873-9ddf-b6bfc4aea33e",
    "Naludugavu" => "3d0548da-7b40-402e-93bb-5d2b689635e2",
    "Namabowe" => "9dc69f9c-2340-4d98-b4e3-0c57420b3bd4",
    "Namafuma" => "30d13606-67d8-41b4-a346-0f40ef572b25",
    "Namakupa" => "64cfb2b5-4c45-4fda-9c68-8357b7488541",
    "Namalala" => "d648cf87-d605-4def-a576-e150048bc390",
    "Namalemba" => "3fa47930-ee1e-4f94-9db4-6b58df36cbaf",
    "Namanve" => "e18ddb4f-eaeb-4895-8d3f-67c68d368e7d",
    "Namasagali" => "6cd398f8-bead-4148-85ff-4827b1d21783",
    "Namasiga-Kidimbuli" => "848e20ff-51fd-4225-8990-065656d0b761",
    "Namatembe" => "2573cd19-d4e7-4a33-8937-d8f86056bf37",
    "Namatiwa" => "74713bd5-58de-43dd-845d-9a30409d5173",
    "Namavundu" => "df04991c-d58f-4b99-b6ae-2b6dc60cb6b6",
    "Namawanyi & Namananga" => "c4047383-040f-4f5a-a224-3f4c2bdf3a31",
    "Namazingiri" => "23218115-120b-4ec5-bd81-f7548e851667",
    "Nambale (Kasa South)" => "bbc9f639-4b38-4717-b926-af40cfaed8e1",
    "Namwasa" => "9675e3d4-b3d3-4894-9522-8755c6652e13",
    "Namyoya" => "cb157597-1f4a-4619-978a-96b32986979f",
    "Nanfuka" => "b35b96a8-8e5c-412b-aebc-f34b91db4126",
    "Nangolibwel" => "6f980582-0f63-4c7a-8f55-0ad233a35953",
    "Napak" => "22a2ad31-3d29-4888-a4e5-aa4b9b4733b0",
    "Napono" => "fc32bee0-4458-4996-87e4-b06fc2785631",
    "Natyonko" => "a86a4d0c-218e-4656-95f2-5230c27c50ce",
    "Navugulu" => "7effa171-12c2-45ca-a27a-6f1535951d67",
    "Nawandigi" => "7b3fb8e1-1ff3-4111-8601-6c55ef994178",
    "Nfuka-Magobwa" => "0a29d2e1-024e-44c8-beee-2ee68e2ee6f6",
    "Ngereka" => "825a74be-102e-4c9d-86a6-5aa3a37b1032",
    "Ngeta" => "616d9d85-4a74-4921-b7de-b2ae83d2b23b",
    "Ngogwe (Bwema Island)" => "589dc608-feb8-4b49-ace8-9becb591e256",
    "Nile Bank" => "3af867ce-632c-4517-aa6a-24919a2fa248",
    "Nimu" => "43ed706f-6794-4d42-91d2-8c4d854173c9",
    "Nkera" => "82a5f717-932f-47bc-9c4f-07c189903732",
    "Nkese" => "5cc8cabc-180b-4a2e-9978-4463aa9b4a2e",
    "Nkogwe" => "f74d22ce-381b-4147-81b4-d1d4440470c0",
    "Nkose" => "e15ded75-85da-48cc-a281-66921f247a48",
    "Nonve" => "e0880651-9781-4f5e-b71e-db54728c7795",
    "North Rwenzori" => "7678e761-b075-4858-a5d3-96b54892732e",
    "Nsekuro Hill" => "6b6b454d-4d42-4fdd-b381-764c2b55ade4",
    "Nsowe" => "87ee0787-04b3-47d0-819d-6d2c3794beb7",
    "Nsube" => "dca003b0-c283-4e97-987d-bc95c48aa876",
    "Ntungamo" => "1fd2b02f-ec86-4227-8cab-090321f99afb",
    "Nyabiku" => "3e905ef5-6e99-4077-8b77-bea947bfff76",
    "Nyaburongo" => "2e1447ef-8849-4258-8cce-c90cd94c3435",
    "Nyabyeya" => "2dc797c4-89fb-4c5b-be50-354529871a7a",
    "Nyakarongo" => "8efa8b1e-a0d7-4657-950b-0d81abfc6e12",
    "Nyakunyu" => "516f8453-eeb2-4692-9afc-0d4ae8cfbf8d",
    "Nyamakere" => "57305afe-f618-4d6e-8f51-722f9f3db586",
    "Nyangea-Napore" => "951d5513-8d5b-4186-9380-37d55cc3ef82",
    "Obel" => "fb5e101f-1fc9-48ff-b32e-9ee51ba0648e",
    "Ocamo-Lum" => "5cf55eff-1ce4-4afd-b559-2d1685b70173",
    "Ochomai" => "31afdb56-ad11-4712-910b-4b899e88ae88",
    "Ochomil" => "f8de10d5-b9be-44bc-971d-38ecc6aaf729",
    "Ogera Hill" => "24bb035e-4b36-4513-b67c-c46dbba20e61",
    "Ogili" => "7b324595-809e-43de-9743-4f0f9427c481",
    "Ogom" => "8d0df2f0-1734-49de-88eb-741c2f3d625f",
    "Ogur" => "0e93ca78-2c7a-4444-9655-06079d19d099",
    "Ojwiting" => "5cc72299-2cf4-4efe-a099-bd32abf3122b",
    "Okavu-Reru" => "1b7dbacb-a76e-4932-aec9-8d4e1e8dcb83",
    "Okurango" => "190aa2e8-ffdb-4d59-8993-6f35120c7c55",
    "Olamusa" => "364fbf44-89d7-4203-a622-645332fb1599",
    "Olia" => "9ffa21a6-100f-42ed-bc37-ebb256030d07",
    "Oliduro" => "684c08cd-058d-4846-ade4-2c1ca98075b9",
    "Olwal" => "0b5f909a-ee50-4afd-ac8e-5b33f4024953",
    "Omier" => "855e7a02-136f-4b29-b1ec-9b61b2830c63",
    "Onekokeo" => "36a478c0-3e40-4344-80b4-8c366233249f",
    "Ongom" => "09492a71-36c4-4068-9bca-bc16f111c3ad",
    "Onyurut" => "9b53cce4-da35-4e5d-b84b-167c1e19e96e",
    "Opaka" => "ab29475f-5ba5-49af-9f08-faebdbc26ffd",
    "Opit" => "311f35b6-9a3a-4358-997b-e9c3a17fbb64",
    "Opok" => "3b50b279-9c05-498c-922f-fd4a0f06c8de",
    "Oruha" => "238dccb0-732b-4596-b8b4-10ff038aecf5",
    "Otrevu" => "4b71d855-4076-469a-99c0-5c3e13bacce1",
    "Otukei" => "8018594d-4c01-4489-8dc1-5e135f817705",
    "Otzi (East)" => "67130634-db67-4d25-a1a4-a392951ac10d",
    "Otzi (West)" => "503489c6-52c4-4c53-be0e-3df18fdef2f2",
    "Ozubu" => "a72e68d3-5ff2-4363-a302-3a6d755ce249",
    "Pajimu" => "09d3335d-2d81-4204-809e-ac4ef3022278",
    "Paonyeme" => "90c5b3ab-a2cb-4b6b-a44b-9d3f123719ce",
    "Parabongo" => "b1d252b5-5b8f-4674-ac5a-89a3536dbcae",
    "Pokoli" => "b05e61f0-f94e-4090-bfae-d732d8e21b73",
    "Rom" => "314773b7-dc44-47c0-9174-598bb5bc2a29",
    "Rugongi" => "d14e5518-1538-44a5-bbd7-d72728228977",
    "Rukara" => "87af2539-a946-4298-8c39-746df7f5024e",
    "Rukungiri" => "223ebf92-f059-4ca8-af14-0d49d6e0857b",
    "Rushaya" => "bf2d5f9c-8e27-41f5-b49d-0c7db0a5a968",
    "Ruzaire" => "ba6ba2c4-cb39-4f60-a23c-e8362d55653a",
    "Rwengeye" => "0f845a2b-3d88-4aa8-94ed-b4644f4f6364",
    "Rwengiri" => "a1d656ff-eac7-4492-80fd-63897e7e427e",
    "Rwensama" => "ea06c643-b034-4565-9ee4-58276ee15d12",
    "Rwensambya" => "64b8b734-1b36-4f50-80d2-b0527c3c3570",
    "Rwoho" => "a3c80cd9-d207-41d2-997c-978236e79796",
    "Sala" => "2faf8a80-2d65-41bf-8b6b-f1e851986600",
    "Sambwa" => "c94421a4-64e9-4eab-b891-7d0eacb2508a",
    "Sekazinga" => "8f969af1-dbfe-48d0-88c2-0e5ecdec1c81",
    "Semunya" => "a3a2c494-6965-4a29-b028-f16fbc9267c2",
    "Sirisiri" => "ba80ff47-c1ea-4305-ba40-1ed76e7dc521",
    "Sitambogo" => "a43f27d1-d417-46b8-9c72-26134a159c25",
    "Soroti" => "5c1f7b7c-0513-404a-a56c-2a6f5ef827c8",
    "South Busoga" => "72711347-7933-41c1-9d77-63161498eb0e",
    "South Maramagambo" => "ac9e6e4f-de24-4895-889b-2a7a1b74ef6a",
    "Sozi" => "fef71ee1-315a-4f6b-8313-6e6f9976be34",
    "Suru" => "c1bc4d79-5b82-44de-9015-0be5842f670c",
    "Taala" => "b6ce0242-4252-4485-a5a0-4b36d99a9fb1",
    "Tebakoli" => "2ddf3ba6-2f37-46a9-879d-142c6ea3ffc4",
    "Telwa" => "2872ebe4-c8cd-4c59-80f0-1b773251af4c",
    "Tero (East)" => "9926897a-1c10-4f5c-9f88-5dde8704f66c",
    "Tero (West)" => "b6828916-ba5b-4fbf-a827-6948b4d6c590",
    "Timu" => "a8bdcaf1-c663-446a-b948-6de8494530d7",
    "Tonde" => "67d04c33-072f-42dc-b67b-39079f45203f",
    "Tororo" => "a3c3756a-15d3-4af4-b7b8-71f5e957f2c8",
    "Towa" => "e550b0ce-1390-4369-af98-86e63bb3c9fc",
    "Tumbi" => "63072f12-75c9-41cc-91cf-d59152a5342e",
    "Usi" => "13942401-1fd4-4b42-a5a9-a45b4b9e78cb",
    "Wabinyomo" => "8edf0169-55c6-4bbf-92c5-ec2951c8b5e0",
    "Wabisi-Wajala" => "cfab3b18-cf5d-4cf1-9948-c5c7abb19a25",
    "Wabitembe" => "419ca9f3-ec93-465b-8f8b-1e067ce1568c",
    "Wadelai" => "fca4cb5b-73ac-4b3e-b592-6eb7a31639f5",
    "Wakayembe" => "ed34257b-b97a-40b3-856b-3879115e027d",
    "Walugogo" => "b2dc9146-a5c5-4b0c-9db6-1652dd21b2a3",
    "Walugondo" => "daf53a1f-b466-41d0-a6fa-ae699cee582d",
    "Walulumbu" => "2de95283-947c-4c56-915f-64df778bdea8",
    "Walumwanyi" => "658acc7e-d0f0-4080-ab87-b5058dcfdc73",
    "Wamale" => "1cfa9520-480d-4e5d-b17a-9acba09da89d",
    "Wamasega" => "13bc12e0-0899-4c72-851a-8b265cf416a5",
    "Wambabya" => "eece12b8-a633-4974-bc44-f80c9a33a128",
    "Wangu" => "dc398485-274f-41f6-9400-bfa5b62bae59",
    "Wankweyo" => "baa42324-10a4-47a2-8240-a333c2d4d620",
    "Wantagalala" => "de9552d0-40e9-416a-8072-ba6a859d1eae",
    "Wantayi" => "1d2b02b9-edd7-4815-8daf-d5e7cf970547",
    "Wati" => "cd8758a9-c4ff-4d2e-998e-f2ccfb8054ae",
    "West Bugwe" => "b0cb20ee-27d6-4799-9f31-b0be667e860b",
    "West Uru" => "ec572c8f-b284-48d1-b2d6-aa0c356695fa",
    "Wiceri" => "97058d2d-0180-4c12-a2d6-a33c3938aafa",
    "Yubwe" => "9ad6c0f1-ed3c-4d06-9ba0-3cb2b7772e87",
    "Zimwa" => "6349f568-1fc3-4505-9039-8eb7ad4db134",
    "Zirimiti" => "76b13922-d6e1-40c7-bdd5-01e01233d848",
    "Zoka" => "1f25dcbe-2d13-47b4-a685-9ec1235ba346",
    "Zulia" => "3f032eaf-09d8-43b7-87fc-140d64004431",
  ];
  // Query all terms in the central forest reserve vocabulary.
  $term_ids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'central_forest_reserve')
    ->accessCheck(FALSE)
    ->execute();
  // Load all terms and set the cfr_global_id field.
  foreach ($term_ids as $tid) {
    $term = Term::load($tid);
    $name = $term->getName();
    if (isset($cfrs[$name])) {
      $term->set('cfr_global_id', $cfrs[$name]);
      $term->save();
    }
  }
}
